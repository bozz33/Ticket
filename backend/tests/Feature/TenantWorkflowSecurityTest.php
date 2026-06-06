<?php

namespace Tests\Feature;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Tenancy\AccessPassCheckinService;
use App\Services\Tenancy\AccessPassService;
use App\Services\Tenancy\OrderService;
use App\Services\Tenancy\ReceiptService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantWorkflowSecurityTest extends TestCase
{
    private string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabasePath = (string) tempnam(sys_get_temp_dir(), 'ticket-tenant-workflow-');

        config()->set('database.connections.tenant.driver', 'sqlite');
        config()->set('database.connections.tenant.database', $this->tenantDatabasePath);
        config()->set('database.connections.tenant.foreign_key_constraints', true);
        config()->set('ticket.tenant_connection', 'tenant');

        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');

        if (isset($this->tenantDatabasePath) && is_file($this->tenantDatabasePath)) {
            @unlink($this->tenantDatabasePath);
        }

        parent::tearDown();
    }

    public function test_order_receipt_and_access_pass_buyer_lookups_are_strict_and_isolated(): void
    {
        [$buyer, $otherBuyer, $order, $receipt, $pass] = $this->seedBuyerWorkflow();

        $orderByUuid = app(OrderService::class)->findByIdentifierForBuyer($buyer, $order->public_id);
        $orderByReference = app(OrderService::class)->findByIdentifierForBuyer($buyer, $order->reference);
        $orderHiddenFromOtherBuyer = app(OrderService::class)->findByIdentifierForBuyer($otherBuyer, $order->public_id);

        $receiptByUuid = app(ReceiptService::class)->findByIdentifierForBuyer($buyer, $receipt->public_id);
        $receiptByReference = app(ReceiptService::class)->findByIdentifierForBuyer($buyer, $receipt->reference);
        $receiptHiddenFromOtherBuyer = app(ReceiptService::class)->findByIdentifierForBuyer($otherBuyer, $receipt->public_id);

        $passByUuid = app(AccessPassService::class)->findByIdentifierForBuyer($buyer, $pass->public_id);
        $passByCode = app(AccessPassService::class)->findByIdentifierForBuyer($buyer, $pass->access_code);
        $passHiddenFromOtherBuyer = app(AccessPassService::class)->findByIdentifierForBuyer($otherBuyer, $pass->public_id);

        $this->assertSame($order->id, $orderByUuid?->id);
        $this->assertSame($order->id, $orderByReference?->id);
        $this->assertNull($orderHiddenFromOtherBuyer);

        $this->assertSame($receipt->id, $receiptByUuid?->id);
        $this->assertSame($receipt->id, $receiptByReference?->id);
        $this->assertNull($receiptHiddenFromOtherBuyer);

        $this->assertSame($pass->id, $passByUuid?->id);
        $this->assertSame($pass->id, $passByCode?->id);
        $this->assertNull($passHiddenFromOtherBuyer);
    }

    public function test_checkin_consume_reset_revoke_and_reactivate_are_consistent_and_audited(): void
    {
        [$buyer, , , , $pass] = $this->seedBuyerWorkflow();

        $request = Request::create('/tenant/checkin', 'POST', [], [], [], [
            'HTTP_X_TERMINAL_ID' => 'terminal-01',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->attributes->set('tenant_user', $buyer);

        $service = app(AccessPassCheckinService::class);

        $preview = $service->preview($pass, $request);
        $consume = $service->consume($pass->fresh(), $request);
        $consumeAgain = $service->consume($pass->fresh(), $request);
        $reset = $service->reset($pass->fresh(), $request);
        $revoke = $service->revoke($pass->fresh(), $request, 'fraud-check');
        $reactivate = $service->reactivate($pass->fresh(), $request);

        $pass->refresh();
        $scans = AccessPassScan::query()->orderBy('id')->get();

        $this->assertSame('granted', $preview['result']);
        $this->assertSame('granted', $consume['result']);
        $this->assertSame('already_used', $consumeAgain['result']);
        $this->assertSame('granted', $reset['result']);
        $this->assertSame('granted', $revoke['result']);
        $this->assertSame('granted', $reactivate['result']);

        $this->assertSame(AccessPassStatus::Active, $pass->status);
        $this->assertCount(6, $scans);
        $this->assertSame('preview', $scans[0]->action);
        $this->assertSame('consume', $scans[1]->action);
        $this->assertSame('consume', $scans[2]->action);
        $this->assertSame('reset', $scans[3]->action);
        $this->assertSame('revoke', $scans[4]->action);
        $this->assertSame('reactivate', $scans[5]->action);
    }

    public function test_reactivate_adds_audit_scan_after_revoke(): void
    {
        [$buyer, , , , $pass] = $this->seedBuyerWorkflow();

        $request = Request::create('/tenant/checkin', 'POST', [], [], [], [
            'HTTP_X_TERMINAL_ID' => 'terminal-02',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->attributes->set('tenant_user', $buyer);

        $service = app(AccessPassCheckinService::class);
        $service->revoke($pass, $request, 'ops');
        $service->reactivate($pass->fresh(), $request);

        $actions = AccessPassScan::query()->orderBy('id')->pluck('action')->all();

        $this->assertSame(['revoke', 'reactivate'], $actions);
    }

    private function seedBuyerWorkflow(): array
    {
        $buyer = User::query()->create([
            'name' => 'Buyer One',
            'username' => 'buyer_one',
            'email' => 'buyer.one@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $otherBuyer = User::query()->create([
            'name' => 'Buyer Two',
            'username' => 'buyer_two',
            'email' => 'buyer.two@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'reference' => 'ORD-SEC-001',
            'transaction_reference' => 'TX-SEC-001',
            'status' => OrderStatus::Confirmed,
            'quantity' => 1,
            'unit_amount' => 1000,
            'total_amount' => 1000,
            'currency_code' => 'XOF',
            'buyer_name' => 'Buyer One',
            'buyer_email' => $buyer->email,
            'buyer_phone' => '+221700000000',
            'meta' => ['source' => 'test'],
        ]);

        $receipt = Receipt::query()->create([
            'reference' => 'RCP-SEC-001',
            'order_id' => $order->id,
            'status' => 'issued',
            'total_amount' => 1000,
            'currency_code' => 'XOF',
            'buyer_name' => 'Buyer One',
            'buyer_email' => $buyer->email,
            'buyer_phone' => '+221700000000',
            'issued_at' => now(),
            'meta' => ['order_reference' => $order->reference],
        ]);

        $pass = AccessPass::query()->create([
            'access_code' => 'PASS-SEC-001',
            'order_id' => $order->id,
            'type' => AccessPassType::PurchasePass,
            'status' => AccessPassStatus::Active,
            'holder_name' => 'Buyer One',
            'holder_email' => $buyer->email,
            'meta' => ['seat_index' => 1],
        ]);

        return [$buyer, $otherBuyer, $order, $receipt, $pass];
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->bigInteger('price_amount')->default(0);
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('transaction_reference')->unique();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('status');
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable()->index();
            $table->string('buyer_phone')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->bigInteger('total_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable()->index();
            $table->string('buyer_phone')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('access_passes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('access_code')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('holder_name')->nullable();
            $table->string('holder_email')->nullable()->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('access_pass_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_pass_id')->constrained('access_passes')->cascadeOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('result');
            $table->string('terminal_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }
}
