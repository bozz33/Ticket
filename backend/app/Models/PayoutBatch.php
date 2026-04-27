<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutBatch extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'payment_gateway_id',
        'reference',
        'status',
        'currency_code',
        'tenant_count',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'scheduled_at',
        'processed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'tenant_count' => 'integer',
            'gross_amount' => 'integer',
            'fee_amount' => 'integer',
            'net_amount' => 'integer',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }
}
