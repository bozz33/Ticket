<?php

namespace App\Models;

use App\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class Plan extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'description',
        'price_amount',
        'currency_code',
        'billing_interval',
        'trial_days',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'billing_interval' => BillingInterval::class,
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function commercialPolicies(): HasMany
    {
        return $this->hasMany(CommercialPolicy::class);
    }

    public function definesFeature(string $code): bool
    {
        $features = Arr::wrap(data_get($this->meta, 'features', []));

        if (array_is_list($features)) {
            return in_array($code, $features, true);
        }

        return (bool) ($features[$code] ?? false);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? 'code', $value)
            ->orWhere('public_id', $value)
            ->first();
    }
}
