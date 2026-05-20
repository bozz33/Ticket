<?php

namespace Ticket\Ticketing\Application;

use App\Models\EventTicket;
use App\Models\Offer;
use App\Models\TicketReservation;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ticket\Ticketing\Contracts\EventTicketInventory;

class EventTicketInventoryService implements EventTicketInventory
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_LOW_STOCK = 'low_stock';

    public const STATUS_SOLD_OUT = 'sold_out';

    public const STATUS_SALES_NOT_STARTED = 'sales_not_started';

    public const STATUS_SALES_ENDED = 'sales_ended';

    public const STATUS_INACTIVE = 'inactive';

    public function snapshot(EventTicket $ticket, ?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();
        $remaining = $this->remaining($ticket);
        $status = $this->status($ticket, $now, $remaining);

        return [
            'status' => $status,
            'label' => $this->label($status, $remaining),
            'remaining' => $remaining,
            'is_sold_out' => $status === self::STATUS_SOLD_OUT,
            'is_available' => $status === self::STATUS_AVAILABLE || $status === self::STATUS_LOW_STOCK,
        ];
    }

    public function remaining(EventTicket $ticket): ?int
    {
        $total = $ticket->quantity_total;

        if ($total === null || $total <= 0) {
            return null;
        }

        return max(0, (int) $total - (int) $ticket->quantity_sold - (int) $ticket->quantity_reserved);
    }

    public function status(EventTicket $ticket, ?CarbonInterface $now = null, ?int $remaining = null): string
    {
        $now ??= Carbon::now();
        $remaining ??= $this->remaining($ticket);

        if (! $ticket->is_active) {
            return self::STATUS_INACTIVE;
        }

        if ($ticket->sales_start_at !== null && $ticket->sales_start_at->greaterThan($now)) {
            return self::STATUS_SALES_NOT_STARTED;
        }

        if ($ticket->sales_end_at !== null && $ticket->sales_end_at->lessThanOrEqualTo($now)) {
            return self::STATUS_SALES_ENDED;
        }

        if ($remaining !== null && $remaining <= 0) {
            return self::STATUS_SOLD_OUT;
        }

        if ($remaining !== null && $remaining <= $this->lowStockThreshold($ticket)) {
            return self::STATUS_LOW_STOCK;
        }

        return self::STATUS_AVAILABLE;
    }

    public function purchasableQuantityBounds(EventTicket $ticket, ?CarbonInterface $now = null): array
    {
        $snapshot = $this->snapshot($ticket, $now);

        if (! $snapshot['is_available']) {
            return [
                'min' => 0,
                'max' => 0,
                'max_per_account' => $ticket->max_per_account ?: null,
            ];
        }

        $min = max(1, (int) ($ticket->min_per_order ?: 1));
        $configuredMax = (int) ($ticket->max_per_order ?: 0);
        $max = $configuredMax > 0 ? $configuredMax : ($snapshot['remaining'] ?? max($min, 10));

        if ($snapshot['remaining'] !== null) {
            $max = min($max, $snapshot['remaining']);
        }

        $maxPerAccount = (int) ($ticket->max_per_account ?: 0);

        if ($maxPerAccount > 0) {
            $max = min($max, $maxPerAccount);
        }

        if ($max < $min) {
            return [
                'min' => 0,
                'max' => 0,
                'max_per_account' => $maxPerAccount > 0 ? $maxPerAccount : null,
            ];
        }

        return [
            'min' => $min,
            'max' => $max,
            'max_per_account' => $maxPerAccount > 0 ? $maxPerAccount : null,
        ];
    }

    public function findByIdentifier(string $identifier): ?EventTicket
    {
        $identifier = trim($identifier);

        if ($identifier === '' || ! $this->eventTicketsTableExists()) {
            return null;
        }

        return EventTicket::query()
            ->with(['offer', 'ticketCategory'])
            ->where(function ($query) use ($identifier): void {
                $query->where('public_id', $identifier)
                    ->orWhere('code', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();
    }

    public function ticketForOffer(Offer $offer): ?EventTicket
    {
        if (! $this->eventTicketsTableExists()) {
            return null;
        }

        $ticketPublicId = data_get($offer->meta ?? [], 'event_ticket_public_id');

        return EventTicket::query()
            ->with(['offer', 'ticketCategory'])
            ->where(function ($query) use ($offer, $ticketPublicId): void {
                $query->where('offer_id', $offer->getKey());

                if (is_string($ticketPublicId) && $ticketPublicId !== '') {
                    $query->orWhere('public_id', $ticketPublicId);
                }
            })
            ->first();
    }

    public function reserve(EventTicket $ticket, int $quantity): EventTicket
    {
        $quantity = max(1, $quantity);

        return DB::connection($ticket->getConnectionName())->transaction(function () use ($ticket, $quantity): EventTicket {
            $lockedTicket = EventTicket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $bounds = $this->purchasableQuantityBounds($lockedTicket);

            if ($quantity < $bounds['min'] || $quantity > $bounds['max']) {
                throw new \RuntimeException('Quantité indisponible pour ce ticket.');
            }

            $lockedTicket->quantity_reserved = (int) $lockedTicket->quantity_reserved + $quantity;
            $lockedTicket->save();

            return $lockedTicket->fresh(['offer', 'ticketCategory']);
        });
    }

    public function releaseReservation(EventTicket $ticket, int $quantity): EventTicket
    {
        $quantity = max(1, $quantity);

        return DB::connection($ticket->getConnectionName())->transaction(function () use ($ticket, $quantity): EventTicket {
            $lockedTicket = EventTicket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTicket->quantity_reserved = max(0, (int) $lockedTicket->quantity_reserved - $quantity);
            $lockedTicket->save();

            return $lockedTicket->fresh(['offer', 'ticketCategory']);
        });
    }

    public function markSold(EventTicket $ticket, int $quantity): EventTicket
    {
        $quantity = max(1, $quantity);

        return DB::connection($ticket->getConnectionName())->transaction(function () use ($ticket, $quantity): EventTicket {
            $lockedTicket = EventTicket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTicket->quantity_reserved = max(0, (int) $lockedTicket->quantity_reserved - $quantity);
            $lockedTicket->quantity_sold = (int) $lockedTicket->quantity_sold + $quantity;
            $lockedTicket->save();

            return $lockedTicket->fresh(['offer', 'ticketCategory']);
        });
    }

    public function releaseReservedCheckout(array $checkout): bool
    {
        if (! $this->eventTicketsTableExists()) {
            return false;
        }

        $ticketId = (int) data_get($checkout, 'event_ticket_id', 0);
        $ticketPublicId = (string) data_get($checkout, 'event_ticket_public_id', '');
        $quantity = max(1, (int) data_get($checkout, 'event_ticket_reserved_quantity', data_get($checkout, 'quantity', 1)));

        $ticket = $ticketId > 0
            ? EventTicket::query()->find($ticketId)
            : $this->findByIdentifier($ticketPublicId);

        if (! $ticket instanceof EventTicket) {
            return false;
        }

        $this->releaseReservation($ticket, $quantity);

        return true;
    }

    public function releaseExpiredReservations(int $olderThanMinutes = 20, int $limit = 100): array
    {
        $summary = [
            'processed' => 0,
            'released' => 0,
            'skipped' => 0,
        ];
        if (! $this->ticketReservationsTableExists()) {
            return $summary;
        }

        TicketReservation::query()
            ->with('eventTicket')
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (TicketReservation $reservation) use (&$summary): void {
                $summary['processed']++;

                if (! $reservation->eventTicket instanceof EventTicket) {
                    $summary['skipped']++;

                    return;
                }

                $this->releaseReservation($reservation->eventTicket, $reservation->quantity);
                $reservation->forceFill([
                    'status' => 'released',
                    'released_at' => now(),
                    'meta' => array_merge((array) ($reservation->meta ?? []), [
                        'release_source' => 'expired_reservation_job',
                        'older_than_minutes' => max(1, $olderThanMinutes),
                    ]),
                ])->save();
                $summary['released']++;
            });

        return $summary;
    }

    private function label(string $status, ?int $remaining): string
    {
        return match ($status) {
            self::STATUS_LOW_STOCK => $remaining === 1 ? 'Dernière place' : 'Dernières places',
            self::STATUS_SOLD_OUT => 'Épuisé',
            self::STATUS_SALES_NOT_STARTED => 'Vente bientôt disponible',
            self::STATUS_SALES_ENDED => 'Vente terminée',
            self::STATUS_INACTIVE => 'Indisponible',
            default => 'Disponible',
        };
    }

    private function lowStockThreshold(EventTicket $ticket): int
    {
        $configured = (int) data_get($ticket->meta ?? [], 'low_stock_threshold', 5);

        return max(1, $configured);
    }

    private function eventTicketsTableExists(): bool
    {
        try {
            return Schema::connection(config('ticket.tenant_connection', 'tenant'))->hasTable('event_tickets');
        } catch (\Throwable) {
            return false;
        }
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
