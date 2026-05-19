<?php

namespace App\Services\Ticketing;

use App\Models\EventTicket;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class EventTicketAvailabilityService
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
            ];
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
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
}
