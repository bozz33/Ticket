<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Offer;
use App\Services\Ticketing\EventTicketOfferSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

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

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

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

        Schema::connection('tenant')->create('event_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
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
    }
}
