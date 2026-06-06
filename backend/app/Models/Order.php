<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasPublicId;
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'buyer_user_id',
        'reference',
        'transaction_reference',
        'offer_id',
        'orderable_type',
        'orderable_id',
        'status',
        'quantity',
        'unit_amount',
        'subtotal_amount',
        'customer_fee_amount',
        'total_amount',
        'refunded_amount',
        'gateway_fee_amount_snapshot',
        'platform_fee_amount_snapshot',
        'tax_amount_snapshot',
        'organizer_net_amount_snapshot',
        'currency_code',
        'refunded_at',
        'refund_reference',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'meta',
        'pricing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'quantity' => 'integer',
            'unit_amount' => 'integer',
            'subtotal_amount' => 'integer',
            'customer_fee_amount' => 'integer',
            'total_amount' => 'integer',
            'refunded_amount' => 'integer',
            'gateway_fee_amount_snapshot' => 'integer',
            'platform_fee_amount_snapshot' => 'integer',
            'tax_amount_snapshot' => 'integer',
            'organizer_net_amount_snapshot' => 'integer',
            'refunded_at' => 'datetime',
            'meta' => 'array',
            'pricing_snapshot' => 'array',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function accessPasses(): HasMany
    {
        return $this->hasMany(AccessPass::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed;
    }
}
