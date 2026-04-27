<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $hidden = [
        'secret_key',
        'webhook_secret',
    ];

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'provider',
        'mode',
        'public_key',
        'secret_key',
        'webhook_secret',
        'supported_currencies',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'supported_currencies' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(GatewayWebhookLog::class);
    }

    public function resolveRouteBinding($value, $field = null): ?EloquentModel
    {
        return $this->newQuery()
            ->where($field ?? 'code', $value)
            ->orWhere('public_id', $value)
            ->first();
    }
}
