<?php

namespace App\Models;

use App\Enums\CommercialModule;
use App\Enums\MonetizationMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialPolicy extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'module',
        'monetization_mode',
        'plan_id',
        'commission_rate',
        'flat_fee_amount',
        'currency_code',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'module' => CommercialModule::class,
            'monetization_mode' => MonetizationMode::class,
            'commission_rate' => 'decimal:2',
            'flat_fee_amount' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
