<?php

namespace App\Models;

use App\Enums\CommercialModule;
use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Enums\RefundFeeBehavior;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeeRule extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'module',
        'country_code',
        'currency_code',
        'charge_bearer',
        'fee_mode',
        'percentage_rate',
        'fixed_amount',
        'cap_amount',
        'vat_rate',
        'refund_behavior',
        'is_active',
        'priority',
        'effective_from',
        'effective_to',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'module' => CommercialModule::class,
            'charge_bearer' => FeeChargeBearer::class,
            'fee_mode' => FeeCalculationMode::class,
            'percentage_rate' => 'decimal:4',
            'fixed_amount' => 'integer',
            'cap_amount' => 'integer',
            'vat_rate' => 'decimal:4',
            'refund_behavior' => RefundFeeBehavior::class,
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
