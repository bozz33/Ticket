<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\EventTicket;
use App\Models\Offer;

interface EventTicketOfferBridge
{
    public function sync(EventTicket $ticket): Offer;

    public function backfillFromEventOffers(): array;

    public function offerForTicketIdentifier(string $identifier): ?Offer;
}
