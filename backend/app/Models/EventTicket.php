<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Services\Ticketing\EventTicketOfferSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTicket extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'event_id',
        'offer_id',
        'name',
        'code',
        'description',
        'ticket_type',
        'price_amount',
        'currency_code',
        'quantity_total',
        'quantity_sold',
        'quantity_reserved',
        'min_per_order',
        'max_per_order',
        'max_per_account',
        'sales_start_at',
        'sales_end_at',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'quantity_total' => 'integer',
            'quantity_sold' => 'integer',
            'quantity_reserved' => 'integer',
            'min_per_order' => 'integer',
            'max_per_order' => 'integer',
            'max_per_account' => 'integer',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (EventTicket $ticket): void {
            if ((bool) data_get($ticket->meta ?? [], 'skip_offer_sync', false)) {
                return;
            }

            if ($ticket->wasChanged('offer_id') && ! $ticket->wasRecentlyCreated) {
                return;
            }

            app(EventTicketOfferSyncService::class)->sync($ticket->fresh(['event', 'offer']));
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
