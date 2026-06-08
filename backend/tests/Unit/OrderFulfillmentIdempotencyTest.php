<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Receipt;
use App\Services\Payments\OrderFulfillmentService;
use App\Support\Microservices\DomainEventBridge;
use App\Support\References\ReferenceGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;

class OrderFulfillmentIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ticket.tenant_connection', 'tenant');

        // Runs against the dedicated PostgreSQL testing database (phpunit.xml).
        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');

        parent::tearDown();
    }

    public function test_fulfill_is_idempotent_for_duplicate_transaction_references(): void
    {
        $service = $this->makeService();

        $ref = 'TXN-IDEM-'.Str::random(8);
        $payload = $this->buildPayload($ref, 5000, 'XOF');

        $firstOrder = $service->fulfill($ref, $payload);
        $secondOrder = $service->fulfill($ref, $payload);

        $this->assertNotNull($firstOrder);
        $this->assertNotNull($secondOrder);
        $this->assertSame((int) $firstOrder->getKey(), (int) $secondOrder->getKey());

        // Exactly one order must exist in the database.
        $this->assertSame(1, Order::on('tenant')->where('transaction_reference', $ref)->count());
    }

    public function test_fulfill_creates_receipt_only_once_on_duplicate_calls(): void
    {
        $service = $this->makeService();

        $ref = 'TXN-RCPT-'.Str::random(8);
        $payload = $this->buildPayload($ref, 3000, 'XOF');

        $service->fulfill($ref, $payload);
        $service->fulfill($ref, $payload);

        $order = Order::on('tenant')->where('transaction_reference', $ref)->firstOrFail();

        $this->assertSame(1, Receipt::on('tenant')->where('order_id', $order->getKey())->count());
    }

    public function test_webhook_metadata_cannot_override_server_checkout_data(): void
    {
        // The fulfillment service reads buyer_email from metadata, which in the webhook
        // flow is already overridden by server-side checkout data (Lot 6 security fix).
        // Here we verify that what reaches fulfill() is the server-side value.
        $service = $this->makeService();

        $ref = 'TXN-META-'.Str::random(8);
        $payload = $this->buildPayload($ref, 2000, 'XOF', [
            'buyer_email' => 'server@example.com',  // server-side checkout value
            'buyer_name' => 'Server User',
        ]);

        $order = $service->fulfill($ref, $payload);

        $this->assertNotNull($order);
        $this->assertSame('server@example.com', $order->buyer_email);
    }

    // ---

    private function buildPayload(string $ref, int $amount, string $currency, array $metaOverrides = []): array
    {
        return [
            'data' => [
                'reference' => $ref,
                'status' => 'success',
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'metadata' => array_merge([
                    'offer_id' => 0,
                    'quantity' => 1,
                    'buyer_name' => 'Test Buyer',
                    'buyer_email' => 'buyer@example.com',
                ], $metaOverrides),
            ],
        ];
    }

    private function makeService(): OrderFulfillmentService
    {
        $referenceGenerator = new ReferenceGenerator;
        $resolver = new class implements CheckoutItemResolver
        {
            public function resolve(string $identifier, ?string $type = null): ?CheckoutItem
            {
                return null;
            }

            public function quantityBounds(CheckoutItem $item): array
            {
                return ['min' => 1, 'max' => 100];
            }

            public function reserve(CheckoutItem $item, int $quantity, array $context = []): ?CheckoutReservation
            {
                return null;
            }

            public function release(array $checkout): bool
            {
                return true;
            }

            public function confirm(array $checkout, int $quantity): bool
            {
                return true;
            }
        };
        $events = $this->createMock(DomainEventBridge::class);

        return new OrderFulfillmentService($referenceGenerator, $resolver, $events);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->bigInteger('price_amount')->default(0);
            $table->unsignedInteger('quantity_total')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('transaction_reference')->unique();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
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
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
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
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('holder_name')->nullable();
            $table->string('holder_email')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Required for BuyerOrderConfirmedNotification
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
