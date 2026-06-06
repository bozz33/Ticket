<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class EventTicket extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'event_id',
        'ticket_category_id',
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
        static::saving(function (self $ticket): void {
            if ($ticket->sales_start_at && $ticket->sales_end_at && $ticket->sales_start_at->greaterThan($ticket->sales_end_at)) {
                throw ValidationException::withMessages([
                    'sales_end_at' => 'La fin des ventes doit être après le début des ventes.',
                ]);
            }

            try {
                $eventDate = $ticket->event?->dates()->first()?->starts_at;
            } catch (\Throwable) {
                $eventDate = null;
            }

            if ($eventDate && $ticket->sales_start_at && $ticket->sales_start_at->greaterThan($eventDate)) {
                throw ValidationException::withMessages([
                    'sales_start_at' => 'Le début des ventes ne peut pas dépasser la date et heure de l’événement.',
                ]);
            }

            if ($eventDate && $ticket->sales_end_at && $ticket->sales_end_at->greaterThan($eventDate)) {
                throw ValidationException::withMessages([
                    'sales_end_at' => 'La fin des ventes ne peut pas dépasser la date et heure de l’événement.',
                ]);
            }
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

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(EventTicketCategory::class, 'ticket_category_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TicketReservation::class);
    }
}
