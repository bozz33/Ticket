<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationLog extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'payment_gateway_id',
        'platform_transaction_id',
        'status',
        'scope',
        'period_start',
        'period_end',
        'discrepancies_count',
        'started_at',
        'completed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'discrepancies_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PlatformTransaction::class, 'platform_transaction_id');
    }
}
