<?php

namespace Tests\Unit;

use App\Models\EventTicket;
use App\Services\Ticketing\EventTicketAvailabilityService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventTicketAvailabilityServiceTest extends TestCase
{
    public function test_it_marks_unlimited_active_ticket_as_available(): void
    {
        $ticket = new EventTicket([
            'name' => 'Standard',
            'is_active' => true,
            'quantity_total' => null,
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_AVAILABLE, $snapshot['status']);
        $this->assertSame('Disponible', $snapshot['label']);
        $this->assertNull($snapshot['remaining']);
        $this->assertTrue($snapshot['is_available']);
        $this->assertFalse($snapshot['is_sold_out']);
    }

    public function test_it_subtracts_sold_and_reserved_quantities_from_remaining_stock(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 100,
            'quantity_sold' => 30,
            'quantity_reserved' => 15,
        ]);

        $this->assertSame(55, $this->service()->remaining($ticket));
    }

    public function test_it_marks_ticket_as_sold_out_when_no_stock_remains(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 10,
            'quantity_sold' => 8,
            'quantity_reserved' => 2,
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_SOLD_OUT, $snapshot['status']);
        $this->assertSame('Épuisé', $snapshot['label']);
        $this->assertSame(0, $snapshot['remaining']);
        $this->assertFalse($snapshot['is_available']);
        $this->assertTrue($snapshot['is_sold_out']);
    }

    public function test_it_marks_ticket_as_low_stock_using_default_threshold(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 10,
            'quantity_sold' => 4,
            'quantity_reserved' => 1,
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_LOW_STOCK, $snapshot['status']);
        $this->assertSame('Dernières places', $snapshot['label']);
        $this->assertSame(5, $snapshot['remaining']);
        $this->assertTrue($snapshot['is_available']);
    }

    public function test_it_marks_ticket_as_inactive(): void
    {
        $ticket = new EventTicket([
            'is_active' => false,
            'quantity_total' => 100,
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_INACTIVE, $snapshot['status']);
        $this->assertSame('Indisponible', $snapshot['label']);
        $this->assertFalse($snapshot['is_available']);
    }

    public function test_it_marks_ticket_as_not_started_before_sales_start(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 100,
            'sales_start_at' => Carbon::parse('2026-05-20 10:00:00'),
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_SALES_NOT_STARTED, $snapshot['status']);
        $this->assertSame('Vente bientôt disponible', $snapshot['label']);
        $this->assertFalse($snapshot['is_available']);
    }

    public function test_it_marks_ticket_as_ended_after_sales_end(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 100,
            'sales_end_at' => Carbon::parse('2026-05-18 10:00:00'),
        ]);

        $snapshot = $this->service()->snapshot($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame(EventTicketAvailabilityService::STATUS_SALES_ENDED, $snapshot['status']);
        $this->assertSame('Vente terminée', $snapshot['label']);
        $this->assertFalse($snapshot['is_available']);
    }

    public function test_it_computes_purchasable_quantity_bounds(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 20,
            'quantity_sold' => 5,
            'quantity_reserved' => 3,
            'min_per_order' => 2,
            'max_per_order' => 10,
            'max_per_account' => 4,
        ]);

        $bounds = $this->service()->purchasableQuantityBounds($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame([
            'min' => 2,
            'max' => 4,
        ], $bounds);
    }

    public function test_it_returns_zero_bounds_when_ticket_is_not_available(): void
    {
        $ticket = new EventTicket([
            'is_active' => true,
            'quantity_total' => 1,
            'quantity_sold' => 1,
        ]);

        $bounds = $this->service()->purchasableQuantityBounds($ticket, Carbon::parse('2026-05-19 10:00:00'));

        $this->assertSame([
            'min' => 0,
            'max' => 0,
        ], $bounds);
    }

    private function service(): EventTicketAvailabilityService
    {
        return new EventTicketAvailabilityService();
    }
}
