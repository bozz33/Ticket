<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReservation extends Model
{
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'event_ticket_id',
        'buyer_user_id',
        'order_id',
        'platform_transaction_reference',
        'buyer_email',
        'quantity',
        'status',
        'reserved_at',
        'expires_at',
        'released_at',
        'confirmed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function eventTicket(): BelongsTo
    {
        return $this->belongsTo(EventTicket::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
