<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayWebhookLog extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'payment_gateway_id',
        'tenant_id',
        'platform_transaction_id',
        'event_name',
        'external_id',
        'signature',
        'headers',
        'payload',
        'status',
        'response_code',
        'failure_reason',
        'processed_at',
        'attempt_count',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'processed_at' => 'datetime',
            'attempt_count' => 'integer',
            'meta' => 'array',
        ];
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PlatformTransaction::class, 'platform_transaction_id');
    }
}
