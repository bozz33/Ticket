<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'platform_transaction_id',
        'payment_gateway_id',
        'reference',
        'gateway_refund_id',
        'gateway_refund_reference',
        'status',
        'gateway_status',
        'reason_code',
        'reason',
        'amount_requested',
        'amount_refunded_to_buyer',
        'organizer_reversal_amount',
        'platform_absorption_amount',
        'gateway_fee_retained',
        'platform_fee_retained',
        'tax_retained',
        'currency_code',
        'processed_at',
        'meta',
        'pricing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount_requested' => 'integer',
            'amount_refunded_to_buyer' => 'integer',
            'organizer_reversal_amount' => 'integer',
            'platform_absorption_amount' => 'integer',
            'gateway_fee_retained' => 'integer',
            'platform_fee_retained' => 'integer',
            'tax_retained' => 'integer',
            'processed_at' => 'datetime',
            'meta' => 'array',
            'pricing_snapshot' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PlatformTransaction::class, 'platform_transaction_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
