<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Offer;
use App\Models\TicketReservation;
use App\Services\Ticketing\EventTicketOfferSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;
use Ticket\Ticketing\Contracts\EventTicketInventory;

class EventTicketOfferSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.tenant.driver', 'sqlite');
        config()->set('database.connections.tenant.database', ':memory:');
        config()->set('database.connections.tenant.foreign_key_constraints', true);

        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    public function test_it_creates_offer_for_event_ticket(): void
    {
        $event = Event::query()->create([
            'public_id' => fake()->uuid(),
            'title' => 'Concert',
            'slug' => 'concert',
            'currency_code' => 'XOF',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $ticket = EventTicket::query()->create([
            'event_id' => $event->getKey(),
            'name' => 'Standard',
            'ticket_type' => 'standard',
            'price_amount' => 5000,
            'currency_code' => 'XOF',
            'quantity_total' => 100,
            'quantity_sold' => 10,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $ticket->refresh();
        $offer = $ticket->offer;

        $this->assertNotNull($offer);
        $this->assertSame(Event::class, $offer->offerable_type);
        $this->assertSame($event->getKey(), $offer->offerable_id);
        $this->assertSame('Standard', $offer->name);
        $this->assertSame(5000, $offer->price_amount);
        $this->assertSame(100, $offer->quantity_total);
        $this->assertSame(10, $offer->quantity_sold);
        $this->assertSame($ticket->public_id, data_get($offer->meta, 'event_ticket_public_id'));
    }

    public function test_it_backfills_event_tickets_from_legacy_event_offers(): void
    {
        $event = Event::query()->create([
            'public_id' => fake()->uuid(),
            'title' => 'Legacy Event',
            'slug' => 'legacy-event',
            'currency_code' => 'XOF',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $offer = Offer::query()->create([
            'public_id' => fake()->uuid(),
            'offerable_type' => Event::class,
            'offerable_id' => $event->getKey(),
            'offer_type' => 'vip',
            'name' => 'VIP',
            'price_amount' => 15000,
            'currency_code' => 'XOF',
            'quantity_total' => 25,
            'quantity_sold' => 5,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'is_active' => true,
        ]);

        $summary = app(EventTicketOfferSyncService::class)->backfillFromEventOffers();
        $ticket = EventTicket::query()->where('offer_id', $offer->getKey())->first();

        $this->assertSame(['created' => 1, 'linked' => 0, 'skipped' => 0], $summary);
        $this->assertNotNull($ticket);
        $this->assertSame('VIP', $ticket->name);
        $this->assertSame('vip', $ticket->ticket_type);
        $this->assertSame(15000, $ticket->price_amount);
        $this->assertSame($offer->public_id, data_get($ticket->meta, 'legacy_offer_public_id'));
    }

    public function test_it_reserves_and_converts_reserved_ticket_stock_to_sold_stock(): void
    {
        $event = Event::query()->create([
            'public_id' => fake()->uuid(),
            'title' => 'Inventory Event',
            'slug' => 'inventory-event',
            'currency_code' => 'XOF',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $ticket = EventTicket::query()->create([
            'event_id' => $event->getKey(),
            'name' => 'Early Bird',
            'ticket_type' => 'early_bird',
            'price_amount' => 3000,
            'currency_code' => 'XOF',
            'quantity_total' => 10,
            'quantity_sold' => 0,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $inventory = app(EventTicketInventory::class);
        $reserved = $inventory->reserve($ticket, 2);

        $this->assertSame(2, $reserved->quantity_reserved);
        $this->assertSame(8, $inventory->remaining($reserved));

        $sold = $inventory->markSold($reserved, 2);

        $this->assertSame(0, $sold->quantity_reserved);
        $this->assertSame(2, $sold->quantity_sold);
        $this->assertSame(2, $sold->offer->fresh()->quantity_sold);
    }

    public function test_checkout_item_resolver_reserves_and_confirms_event_ticket_stock(): void
    {
        $event = Event::query()->create([
            'public_id' => fake()->uuid(),
            'title' => 'Checkout Event',
            'slug' => 'checkout-event',
            'currency_code' => 'XOF',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $ticket = EventTicket::query()->create([
            'event_id' => $event->getKey(),
            'name' => 'VIP Checkout',
            'ticket_type' => 'vip',
            'price_amount' => 12000,
            'currency_code' => 'XOF',
            'quantity_total' => 5,
            'quantity_sold' => 0,
            'quantity_reserved' => 0,
            'is_active' => true,
        ])->refresh();

        $resolver = app(CheckoutItemResolver::class);
        $offerCountBeforeResolve = Offer::query()->count();
        $item = $resolver->resolve($ticket->public_id, 'event_ticket');

        $this->assertInstanceOf(CheckoutItem::class, $item);
        $this->assertSame('event_ticket', $item->type);
        $this->assertSame($ticket->public_id, $item->publicId);
        $this->assertNull($item->pricingOffer);
        $this->assertSame($offerCountBeforeResolve, Offer::query()->count());

        $reservation = $resolver->reserve($item, 2, [
            'transaction_reference' => 'PAY-TEST-001',
            'buyer_email' => 'buyer@example.test',
        ]);

        $this->assertInstanceOf(CheckoutReservation::class, $reservation);
        $this->assertSame(2, $ticket->fresh()->quantity_reserved);

        $storedReservation = TicketReservation::query()->first();

        $this->assertNotNull($storedReservation);
        $this->assertSame('pending', $storedReservation->status);
        $this->assertSame('PAY-TEST-001', $storedReservation->platform_transaction_reference);

        $confirmed = $resolver->confirm(array_merge($item->metadata, $reservation->metadata), 2);

        $this->assertTrue($confirmed);
        $this->assertSame('confirmed', $storedReservation->fresh()->status);
        $this->assertSame(0, $ticket->fresh()->quantity_reserved);
        $this->assertSame(2, $ticket->fresh()->quantity_sold);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('offerable_type');
            $table->unsignedBigInteger('offerable_id');
            $table->string('offer_type', 100)->default('standard')->index();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->string('currency_code', 3)->nullable()->index();
            $table->unsignedInteger('quantity_total')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('min_per_order')->default(1);
            $table->unsignedInteger('max_per_order')->nullable();
            $table->unsignedInteger('max_per_account')->nullable();
            $table->timestamp('sales_start_at')->nullable()->index();
            $table->timestamp('sales_end_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('event_ticket_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('event_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('ticket_category_id')->nullable()->constrained('event_ticket_categories')->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->longText('description')->nullable();
            $table->string('ticket_type', 100)->default('standard')->index();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->string('currency_code', 3)->nullable()->index();
            $table->unsignedInteger('quantity_total')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('min_per_order')->default(1);
            $table->unsignedInteger('max_per_order')->nullable();
            $table->unsignedInteger('max_per_account')->nullable();
            $table->timestamp('sales_start_at')->nullable()->index();
            $table->timestamp('sales_end_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('ticket_reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('event_ticket_id')->constrained('event_tickets')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('platform_transaction_reference')->nullable()->index();
            $table->string('buyer_email')->nullable()->index();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('status', 40)->default('pending')->index();
            $table->timestamp('reserved_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('released_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
