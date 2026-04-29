<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'reference',
        'transaction_reference',
        'offer_id',
        'status',
        'quantity',
        'unit_amount',
        'total_amount',
        'currency_code',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'quantity' => 'integer',
            'unit_amount' => 'integer',
            'total_amount' => 'integer',
            'meta' => 'array',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
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
