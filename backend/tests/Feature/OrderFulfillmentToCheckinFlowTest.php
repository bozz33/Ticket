<?php

namespace Tests\Feature;

use App\Enums\AccessPassStatus;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Payments\OrderFulfillmentService;
use App\Services\Tenancy\AccessPassCheckinService;
use App\Support\Microservices\DomainEventBridge;
use App\Support\References\ReferenceGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;

/**
 * End-to-end integration of the money path: a confirmed payment is fulfilled into an
 * Order + Receipt + AccessPasses, and those same passes are then scanned at check-in.
 *
 * Unit tests already cover each link in isolation (fulfillment idempotency, the check-in
 * state machine, webhook handling). This test ties the chain together so a contract drift
 * between the fulfillment output and the check-in input is caught: pass count vs ordered
 * quantity, amount consistency across order/receipt, and double-scan rejection on a real
 * fulfilled pass rather than a hand-seeded one.
 */
class OrderFulfillmentToCheckinFlowTest extends TestCase
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

    public function test_fulfilled_passes_can_be_scanned_and_reject_a_second_scan(): void
    {
        $offer = Offer::on('tenant')->create([
            'title' => 'General admission',
            'price_amount' => 2500,
            'quantity_total' => 5,
            'quantity_sold' => 0,
        ]);

        $scanner = User::on('tenant')->create([
            'name' => 'Gate Scanner',
            'email' => 'scanner@example.test',
        ]);

        $ref = 'TXN-FLOW-'.Str::random(8);
        $quantity = 2;
        $payload = $this->buildPayload($ref, (int) $offer->getKey(), $quantity, $offer->price_amount * $quantity);

        $order = $this->makeFulfillmentService()->fulfill($ref, $payload);

        // --- Fulfillment contract ---------------------------------------------------
        $this->assertNotNull($order);
        $this->assertSame($quantity, (int) $order->quantity);
        $this->assertSame(2500, (int) $order->unit_amount);
        $this->assertSame(5000, (int) $order->total_amount);
        $this->assertSame('XOF', $order->currency_code);

        $receipt = Receipt::on('tenant')->where('order_id', $order->getKey())->firstOrFail();
        $this->assertSame((int) $order->total_amount, (int) $receipt->total_amount);

        $passes = AccessPass::on('tenant')->where('order_id', $order->getKey())->orderBy('id')->get();
        $this->assertCount($quantity, $passes);
        $this->assertTrue($passes->every(fn (AccessPass $p): bool => $p->status === AccessPassStatus::Active));

        // Stock was consumed exactly once for the ordered quantity.
        $this->assertSame($quantity, (int) $offer->fresh()->quantity_sold);

        // --- Check-in ties to the fulfilled pass ------------------------------------
        $request = Request::create('/tenant/checkin', 'POST', [], [], [], [
            'HTTP_X_TERMINAL_ID' => 'gate-01',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->attributes->set('tenant_user', $scanner);

        $service = app(AccessPassCheckinService::class);

        $first = $passes->first();
        $second = $passes->last();

        $consume = $service->consume($first->fresh(), $request);
        $consumeAgain = $service->consume($first->fresh(), $request);

        $this->assertSame('granted', $consume['result']);
        $this->assertSame('already_used', $consumeAgain['result']);

        // The first pass is used exactly once; the second pass is untouched.
        $this->assertSame(AccessPassStatus::Used, $first->fresh()->status);
        $this->assertSame(AccessPassStatus::Active, $second->fresh()->status);

        // Two consume attempts were audited, but only one flipped the pass to used.
        $consumeScans = AccessPassScan::on('tenant')
            ->where('access_pass_id', $first->getKey())
            ->where('action', 'consume')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $consumeScans);
        $this->assertSame('granted', $consumeScans[0]->result->value);
        $this->assertSame('already_used', $consumeScans[1]->result->value);
    }

    public function test_fulfillment_refuses_to_oversell_beyond_offer_stock(): void
    {
        $offer = Offer::on('tenant')->create([
            'title' => 'Last seat',
            'price_amount' => 2500,
            'quantity_total' => 1,
            'quantity_sold' => 0,
        ]);

        $ref = 'TXN-OVERSELL-'.Str::random(8);
        $payload = $this->buildPayload($ref, (int) $offer->getKey(), 2, $offer->price_amount * 2);

        try {
            $this->makeFulfillmentService()->fulfill($ref, $payload);
            $this->fail('Fulfillment should have rejected an order exceeding available stock.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
        }

        // The transaction rolled back: no order, no passes, stock untouched.
        $this->assertSame(0, Order::on('tenant')->where('transaction_reference', $ref)->count());
        $this->assertSame(0, AccessPass::on('tenant')->count());
        $this->assertSame(0, (int) $offer->fresh()->quantity_sold);
    }

    // ---

    private function buildPayload(string $ref, int $offerId, int $quantity, int $amount): array
    {
        return [
            'data' => [
                'reference' => $ref,
                'status' => 'success',
                'amount' => $amount,
                'currency' => 'XOF',
                'fees' => 0,
                'metadata' => [
                    'offer_id' => $offerId,
                    'quantity' => $quantity,
                    'buyer_name' => 'Flow Buyer',
                    'buyer_email' => 'flow.buyer@example.test',
                ],
            ],
        ];
    }

    private function makeFulfillmentService(): OrderFulfillmentService
    {
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
                // An Offer-backed purchase is not resolver-managed (unlike an EventTicket),
                // so confirm() reports "not handled" and fulfillment falls back to the
                // offer's own quantity_sold counter as the stock authority.
                return false;
            }
        };

        return new OrderFulfillmentService(
            new ReferenceGenerator,
            $resolver,
            $this->createMock(DomainEventBridge::class),
        );
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->nullable()->unique();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->bigInteger('price_amount')->default(0);
            $table->unsignedInteger('quantity_total')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->string('offerable_type')->nullable();
            $table->unsignedBigInteger('offerable_id')->nullable();
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

        // Required for BuyerOrderConfirmedNotification dispatched during fulfillment.
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
