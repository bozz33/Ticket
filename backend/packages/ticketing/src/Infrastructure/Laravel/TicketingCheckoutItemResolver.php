<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\EventTicket;
use App\Models\TicketReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;
use Ticket\Payments\Infrastructure\Laravel\OfferCheckoutItemResolver;
use Ticket\Ticketing\Contracts\EventTicketInventory;

class TicketingCheckoutItemResolver implements CheckoutItemResolver
{
    public function __construct(
        private readonly EventTicketInventory $inventory,
        private readonly OfferCheckoutItemResolver $offers,
    ) {}

    public function resolve(string $identifier, ?string $type = null): ?CheckoutItem
    {
        $ticket = null;

        if ($type === null || in_array($type, ['event_ticket', 'ticket'], true)) {
            $ticket = $this->inventory->findByIdentifier($identifier);
        }

        if ($ticket instanceof EventTicket) {
            return $this->ticketItem($ticket);
        }

        return $this->offers->resolve($identifier, $type);
    }

    public function quantityBounds(CheckoutItem $item): array
    {
        if (! $item->is('event_ticket')) {
            return $this->offers->quantityBounds($item);
        }

        $ticket = $this->ticketFromItem($item);

        if (! $ticket instanceof EventTicket) {
            throw new \RuntimeException('Ticket introuvable.');
        }

        $bounds = $this->inventory->purchasableQuantityBounds($ticket);

        if ($bounds['max'] < 1) {
            throw new \RuntimeException('Ce ticket est indisponible pour le moment.');
        }

        return $bounds;
    }

    public function reserve(CheckoutItem $item, int $quantity, array $context = []): ?CheckoutReservation
    {
        if (! $item->is('event_ticket')) {
            return null;
        }

        $ticket = $this->ticketFromItem($item);

        if (! $ticket instanceof EventTicket) {
            throw new \RuntimeException('Ticket introuvable.');
        }

        $existingReservation = $this->existingReservationFromContext($ticket, $quantity, $context);

        if ($existingReservation instanceof TicketReservation) {
            return new CheckoutReservation(
                type: 'event_ticket',
                publicId: $existingReservation->public_id,
                quantity: $existingReservation->quantity,
                expiresAt: $existingReservation->expires_at,
                metadata: [
                    'ticket_reservation_id' => $existingReservation->getKey(),
                    'ticket_reservation_public_id' => $existingReservation->public_id,
                    'ticket_reservation_expires_at' => $existingReservation->expires_at?->toIso8601String(),
                ],
            );
        }

        $reservedTicket = $this->inventory->reserve($ticket, $quantity);
        $reservation = $this->createReservation($reservedTicket, $quantity, $context);

        return new CheckoutReservation(
            type: 'event_ticket',
            publicId: $reservation?->public_id ?? $reservedTicket->public_id,
            quantity: $quantity,
            expiresAt: $reservation?->expires_at,
            metadata: array_filter([
                'ticket_reservation_id' => $reservation?->getKey(),
                'ticket_reservation_public_id' => $reservation?->public_id,
                'ticket_reservation_expires_at' => $reservation?->expires_at?->toIso8601String(),
            ]),
        );
    }

    public function release(array $checkout): bool
    {
        $reservation = $this->reservationFromCheckout($checkout);

        if ($reservation instanceof TicketReservation && $reservation->status === 'pending') {
            if (! $reservation->eventTicket instanceof EventTicket) {
                return false;
            }

            $this->inventory->releaseReservation($reservation->eventTicket, $reservation->quantity);
            $reservation->forceFill([
                'status' => 'released',
                'released_at' => now(),
                'meta' => array_merge((array) ($reservation->meta ?? []), [
                    'release_source' => 'checkout_release',
                ]),
            ])->save();

            return true;
        }

        return $this->inventory->releaseReservedCheckout($checkout);
    }

    public function confirm(array $checkout, int $quantity): bool
    {
        $reservation = $this->reservationFromCheckout($checkout);

        if ($reservation instanceof TicketReservation) {
            if ($reservation->status === 'confirmed') {
                return true;
            }

            if ($reservation->status !== 'pending') {
                return false;
            }

            if (! $reservation->eventTicket instanceof EventTicket) {
                return false;
            }

            $this->inventory->markSold($reservation->eventTicket, $quantity);
            $reservation->forceFill([
                'order_id' => (int) data_get($checkout, 'order_id', 0) ?: $reservation->order_id,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ])->save();

            return true;
        }

        $ticketId = (int) data_get($checkout, 'event_ticket_id', 0);
        $ticket = $ticketId > 0 ? EventTicket::query()->find($ticketId) : null;

        if (! $ticket instanceof EventTicket) {
            return false;
        }

        $this->inventory->markSold($ticket, $quantity);

        return true;
    }

    private function ticketItem(EventTicket $ticket): CheckoutItem
    {
        return new CheckoutItem(
            type: 'event_ticket',
            publicId: $ticket->public_id,
            title: $ticket->name,
            unitAmount: $ticket->price_amount,
            currencyCode: $ticket->currency_code,
            orderableType: EventTicket::class,
            orderableId: $ticket->getKey(),
            metadata: [
                'event_ticket_id' => $ticket->getKey(),
                'event_ticket_public_id' => $ticket->public_id,
                'event_ticket_title' => $ticket->name,
                'event_ticket_category' => $ticket->ticketCategory?->name,
                'event_ticket_category_code' => $ticket->ticketCategory?->code,
                'availability' => $this->inventory->snapshot($ticket),
            ],
        );
    }

    private function ticketFromItem(CheckoutItem $item): ?EventTicket
    {
        if ($item->orderableType !== EventTicket::class || $item->orderableId === null) {
            return null;
        }

        return EventTicket::query()->with(['ticketCategory'])->find($item->orderableId);
    }

    private function createReservation(EventTicket $ticket, int $quantity, array $context): ?TicketReservation
    {
        if (! $this->ticketReservationsTableExists()) {
            return null;
        }

        $connectionName = $ticket->getConnectionName() ?: config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($ticket, $quantity, $context): TicketReservation {
            return TicketReservation::query()->create([
                'event_ticket_id' => $ticket->getKey(),
                'buyer_user_id' => (int) ($context['buyer_user_id'] ?? 0) ?: null,
                'platform_transaction_reference' => (string) ($context['transaction_reference'] ?? ''),
                'buyer_email' => (string) ($context['buyer_email'] ?? ''),
                'quantity' => max(1, $quantity),
                'status' => 'pending',
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(max(1, (int) config('ticket.ticket_reservations.ttl_minutes', 20))),
                'meta' => [
                    'checkout_item_type' => 'event_ticket',
                    'checkout_reference' => (string) ($context['transaction_reference'] ?? ''),
                ],
            ]);
        });
    }

    private function existingReservationFromContext(EventTicket $ticket, int $quantity, array $context): ?TicketReservation
    {
        $reservationPublicId = (string) ($context['ticket_reservation_public_id'] ?? $context['reservation_public_id'] ?? '');

        if ($reservationPublicId === '' || ! $this->ticketReservationsTableExists()) {
            return null;
        }

        $reservation = TicketReservation::query()
            ->where('public_id', $reservationPublicId)
            ->where('event_ticket_id', $ticket->getKey())
            ->where('status', 'pending')
            ->first();

        if (! $reservation instanceof TicketReservation) {
            return null;
        }

        if ($reservation->expires_at !== null && $reservation->expires_at->isPast()) {
            return null;
        }

        if ($reservation->quantity !== max(1, $quantity)) {
            throw new \RuntimeException('La quantité ne correspond pas à la réservation.');
        }

        return $reservation;
    }

    private function reservationFromCheckout(array $checkout): ?TicketReservation
    {
        if (! $this->ticketReservationsTableExists()) {
            return null;
        }

        $reservationId = (int) data_get($checkout, 'ticket_reservation_id', 0);
        $reservationPublicId = (string) data_get($checkout, 'ticket_reservation_public_id', '');

        if ($reservationId <= 0 && $reservationPublicId === '') {
            return null;
        }

        return TicketReservation::query()
            ->with('eventTicket')
            ->where(function ($query) use ($reservationId, $reservationPublicId): void {
                if ($reservationId > 0) {
                    $query->whereKey($reservationId);
                }

                if ($reservationPublicId !== '') {
                    $query->orWhere('public_id', $reservationPublicId);
                }
            })
            ->first();
    }

    private function ticketReservationsTableExists(): bool
    {
        try {
            return Schema::connection(config('ticket.tenant_connection', 'tenant'))->hasTable('ticket_reservations');
        } catch (\Throwable) {
            return false;
        }
    }
}
