<?php

namespace Tests\Feature;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\AccessPass;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\Refund;
use App\Services\Payments\TenantRefundService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantRefundServiceTest extends TestCase
{
    private string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabasePath = (string) tempnam(sys_get_temp_dir(), 'ticket-tenant-refund-service-');

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

    public function test_apply_marks_order_receipt_and_pass_as_refund_pending(): void
    {
        [$order, $receipt, $pass] = $this->seedOrderBundle();

        $refund = new Refund([
            'reference' => 'RFD-TEST-001',
            'status' => RefundStatus::Pending,
            'amount_refunded_to_buyer' => 11200,
            'processed_at' => now(),
        ]);

        app(TenantRefundService::class)->apply($refund, $order);

        $order->refresh();
        $receipt->refresh();
        $pass->refresh();

        $this->assertSame(OrderStatus::RefundPending, $order->status);
        $this->assertSame(11200, $order->refunded_amount);
        $this->assertSame('RFD-TEST-001', $order->refund_reference);
        $this->assertSame('refund_pending', $receipt->status);
        $this->assertSame(11200, $receipt->refunded_amount);
        $this->assertSame(AccessPassStatus::Revoked, $pass->status);
        $this->assertSame('refund:RFD-TEST-001', $pass->revocation_reason);
    }

    public function test_apply_reverts_pending_refund_when_gateway_fails(): void
    {
        [$order, $receipt, $pass] = $this->seedOrderBundle();

        $pendingRefund = new Refund([
            'reference' => 'RFD-TEST-002',
            'status' => RefundStatus::Pending,
            'amount_refunded_to_buyer' => 11200,
            'processed_at' => now(),
        ]);

        app(TenantRefundService::class)->apply($pendingRefund, $order);

        $failedRefund = new Refund([
            'reference' => 'RFD-TEST-002',
            'status' => RefundStatus::Failed,
            'amount_refunded_to_buyer' => 11200,
            'processed_at' => now(),
        ]);

        app(TenantRefundService::class)->apply($failedRefund, $order->fresh());

        $order->refresh();
        $receipt->refresh();
        $pass->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(0, $order->refunded_amount);
        $this->assertNull($order->refund_reference);
        $this->assertSame('issued', $receipt->status);
        $this->assertSame(0, $receipt->refunded_amount);
        $this->assertSame(AccessPassStatus::Active, $pass->status);
        $this->assertNull($pass->revocation_reason);
    }

    public function test_apply_marks_order_as_refunded_when_refund_is_processed(): void
    {
        [$order, $receipt, $pass] = $this->seedOrderBundle();

        $refund = new Refund([
            'reference' => 'RFD-TEST-003',
            'status' => RefundStatus::Refunded,
            'amount_refunded_to_buyer' => 11200,
            'processed_at' => now(),
        ]);

        app(TenantRefundService::class)->apply($refund, $order);

        $order->refresh();
        $receipt->refresh();
        $pass->refresh();

        $this->assertSame(OrderStatus::Refunded, $order->status);
        $this->assertSame(11200, $order->refunded_amount);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame('refunded', $receipt->status);
        $this->assertSame(11200, $receipt->refunded_amount);
        $this->assertNotNull($receipt->refunded_at);
        $this->assertSame(AccessPassStatus::Revoked, $pass->status);
    }

    private function seedOrderBundle(): array
    {
        $order = Order::query()->create([
            'reference' => 'ORD-RFD-001',
            'transaction_reference' => 'PAY-RFD-001',
            'status' => OrderStatus::Confirmed,
            'quantity' => 1,
            'unit_amount' => 10000,
            'subtotal_amount' => 10000,
            'customer_fee_amount' => 1200,
            'total_amount' => 11200,
            'gateway_fee_amount_snapshot' => 200,
            'platform_fee_amount_snapshot' => 1000,
            'tax_amount_snapshot' => 200,
            'organizer_net_amount_snapshot' => 9800,
            'currency_code' => 'XOF',
            'buyer_name' => 'Refund Buyer',
            'buyer_email' => 'refund.buyer@example.test',
            'buyer_phone' => '+2250700000000',
        ]);

        $receipt = Receipt::query()->create([
            'reference' => 'RCP-RFD-001',
            'order_id' => $order->id,
            'status' => 'issued',
            'total_amount' => 11200,
            'currency_code' => 'XOF',
            'buyer_name' => 'Refund Buyer',
            'buyer_email' => 'refund.buyer@example.test',
            'buyer_phone' => '+2250700000000',
            'issued_at' => now(),
        ]);

        $pass = AccessPass::query()->create([
            'access_code' => 'PASS-RFD-001',
            'order_id' => $order->id,
            'type' => AccessPassType::EventTicket,
            'status' => AccessPassStatus::Active,
            'holder_name' => 'Refund Buyer',
            'holder_email' => 'refund.buyer@example.test',
        ]);

        return [$order, $receipt, $pass];
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('transaction_reference')->unique();
            $table->string('status');
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_amount')->default(0);
            $table->bigInteger('subtotal_amount')->default(0);
            $table->bigInteger('customer_fee_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('refunded_amount')->default(0);
            $table->bigInteger('gateway_fee_amount_snapshot')->default(0);
            $table->bigInteger('platform_fee_amount_snapshot')->default(0);
            $table->bigInteger('tax_amount_snapshot')->default(0);
            $table->bigInteger('organizer_net_amount_snapshot')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reference')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable()->index();
            $table->string('buyer_phone')->nullable();
            $table->json('meta')->nullable();
            $table->json('pricing_snapshot')->nullable();
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
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable()->index();
            $table->string('buyer_phone')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('access_passes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('access_code')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
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
    }
}
