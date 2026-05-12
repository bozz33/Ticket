<?php

namespace App\Models;

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Enums\PaymentChannel;
use App\Enums\RefundFeeBehavior;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayFeeRule extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'payment_gateway_id',
        'name',
        'country_code',
        'currency_code',
        'payment_channel',
        'charge_bearer',
        'fee_mode',
        'percentage_rate',
        'fixed_amount',
        'cap_amount',
        'vat_rate',
        'refund_behavior',
        'is_active',
        'is_gross_up_enabled',
        'priority',
        'effective_from',
        'effective_to',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'payment_channel' => PaymentChannel::class,
            'charge_bearer' => FeeChargeBearer::class,
            'fee_mode' => FeeCalculationMode::class,
            'percentage_rate' => 'decimal:4',
            'fixed_amount' => 'integer',
            'cap_amount' => 'integer',
            'vat_rate' => 'decimal:4',
            'refund_behavior' => RefundFeeBehavior::class,
            'is_active' => 'boolean',
            'is_gross_up_enabled' => 'boolean',
            'priority' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
