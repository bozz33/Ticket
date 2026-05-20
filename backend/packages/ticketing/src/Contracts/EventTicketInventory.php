<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\EventTicket;
use App\Models\Offer;
use Carbon\CarbonInterface;

interface EventTicketInventory
{
    public function snapshot(EventTicket $ticket, ?CarbonInterface $now = null): array;

    public function remaining(EventTicket $ticket): ?int;

    public function status(EventTicket $ticket, ?CarbonInterface $now = null, ?int $remaining = null): string;

    public function purchasableQuantityBounds(EventTicket $ticket, ?CarbonInterface $now = null): array;

    public function findByIdentifier(string $identifier): ?EventTicket;

    public function ticketForOffer(Offer $offer): ?EventTicket;

    public function reserve(EventTicket $ticket, int $quantity): EventTicket;

    public function releaseReservation(EventTicket $ticket, int $quantity): EventTicket;

    public function markSold(EventTicket $ticket, int $quantity): EventTicket;

    public function releaseReservedCheckout(array $checkout): bool;

    public function releaseExpiredReservations(int $olderThanMinutes = 20, int $limit = 100): array;
}
