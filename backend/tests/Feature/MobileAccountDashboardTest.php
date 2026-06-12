<?php

namespace Tests\Feature;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Api\V1\Mobile\MobileAccountDashboardController;
use App\Models\AccessPass;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Behavioural coverage for the mobile buyer dashboard: it must aggregate only the
 * authenticated buyer's own orders, passes and receipts (tenant buyer-scoping) and expose
 * the documented response shape.
 */
class MobileAccountDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ticket.tenant_connection', 'tenant');
        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');

        parent::tearDown();
    }

    public function test_dashboard_aggregates_only_the_authenticated_buyer_data(): void
    {
        $buyer = $this->makeUser('buyer@example.test');
        $other = $this->makeUser('intruder@example.test');

        // Buyer's own records.
        $orderA = $this->makeOrder($buyer, 'ORD-A', 'TX-A');
        $this->makeOrder($buyer, 'ORD-B', 'TX-B');
        $this->makeReceipt($buyer, $orderA, 'RCP-A');
        $this->makePass($buyer, $orderA, 'PASS-A', AccessPassStatus::Active);
        $this->makePass($buyer, $orderA, 'PASS-USED', AccessPassStatus::Used);

        // Another buyer's records must never leak into the dashboard.
        $orderC = $this->makeOrder($other, 'ORD-C', 'TX-C');
        $this->makeReceipt($other, $orderC, 'RCP-C');
        $this->makePass($other, $orderC, 'PASS-C', AccessPassStatus::Active);

        $request = Request::create('/tenant/mobile/account/dashboard', 'GET');
        $request->attributes->set('tenant_user', $buyer);

        $response = app(MobileAccountDashboardController::class)($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());

        $summary = $payload['data']['summary'];
        $this->assertSame(2, $summary['orders_count']);
        $this->assertSame(1, $summary['active_passes_count']); // the used pass is excluded
        $this->assertSame(1, $summary['receipts_count']);

        $this->assertSame($buyer->email, $payload['data']['user']['email']);
        $this->assertCount(2, $payload['data']['recent_orders']);
        $this->assertCount(1, $payload['data']['active_passes']);
        $this->assertArrayHasKey('qr_payload', $payload['data']['active_passes'][0]);

        // No record belonging to the other buyer is present.
        $orderRefs = array_column($payload['data']['recent_orders'], 'reference');
        $this->assertNotContains('ORD-C', $orderRefs);
    }

    // ---

    private function makeUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Buyer '.$email,
            'email' => $email,
            'password' => 'password123',
            'is_active' => true,
        ]);
    }

    private function makeOrder(User $user, string $reference, string $txReference): Order
    {
        return Order::query()->create([
            'buyer_user_id' => $user->getKey(),
            'reference' => $reference,
            'transaction_reference' => $txReference,
            'status' => OrderStatus::Confirmed,
            'quantity' => 1,
            'unit_amount' => 1000,
            'total_amount' => 1000,
            'currency_code' => 'XOF',
            'buyer_email' => $user->email,
        ]);
    }

    private function makeReceipt(User $user, Order $order, string $reference): Receipt
    {
        return Receipt::query()->create([
            'buyer_user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'reference' => $reference,
            'receipt_number' => 'RCP-2026-'.substr($reference, -1).'00001',
            'status' => 'issued',
            'total_amount' => 1000,
            'currency_code' => 'XOF',
            'issued_at' => now(),
        ]);
    }

    private function makePass(User $user, Order $order, string $code, AccessPassStatus $status): AccessPass
    {
        return AccessPass::query()->create([
            'access_code' => $code,
            'order_id' => $order->getKey(),
            'holder_user_id' => $user->getKey(),
            'type' => AccessPassType::PurchasePass,
            'status' => $status,
            'holder_name' => $user->name,
            'holder_email' => $user->email,
            'meta' => ['seat_index' => 1],
        ]);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('transaction_reference')->unique();
            $table->string('status');
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('refunded_amount')->default(0);
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
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('receipt_number')->nullable();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
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
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('holder_name')->nullable();
            $table->string('holder_email')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('call_for_project_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('applicant_email')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('crowdfunding_contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_user_id')->nullable();
            $table->string('contributor_email')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
