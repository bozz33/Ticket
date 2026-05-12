<?php

namespace App\Models;

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutPolicy extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'country_code',
        'currency_code',
        'minimum_payout_amount',
        'reserve_rate',
        'reserve_days',
        'payout_delay_days',
        'charge_bearer',
        'payout_fee_mode',
        'payout_fee_percentage',
        'payout_fee_fixed',
        'payout_fee_cap',
        'auto_payout_enabled',
        'requires_manual_review',
        'is_active',
        'priority',
        'effective_from',
        'effective_to',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'charge_bearer' => FeeChargeBearer::class,
            'payout_fee_mode' => FeeCalculationMode::class,
            'minimum_payout_amount' => 'integer',
            'reserve_rate' => 'decimal:4',
            'reserve_days' => 'integer',
            'payout_delay_days' => 'integer',
            'payout_fee_percentage' => 'decimal:4',
            'payout_fee_fixed' => 'integer',
            'payout_fee_cap' => 'integer',
            'auto_payout_enabled' => 'boolean',
            'requires_manual_review' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
