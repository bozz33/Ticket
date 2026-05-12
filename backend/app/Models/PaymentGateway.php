<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;
    use HasPublicId;

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
        'supported_countries',
        'supported_channels',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'supported_currencies' => 'array',
            'supported_countries' => 'array',
            'supported_channels' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class);
    }

    public function feeRules(): HasMany
    {
        return $this->hasMany(GatewayFeeRule::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(GatewayWebhookLog::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function resolveRouteBinding($value, $field = null): ?EloquentModel
    {
        return $this->newQuery()
            ->where($field ?? 'code', $value)
            ->orWhere('public_id', $value)
            ->first();
    }
}
