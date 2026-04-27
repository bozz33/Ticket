<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Support\Tenancy\StanclTenantDatabaseConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\InvalidatesResolverCache;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\DatabaseConfig;
use Stancl\Tenancy\Events;

class Tenant extends Model implements TenantWithDatabase
{
    use HasFactory, InvalidatesResolverCache, SoftDeletes, TenantRun;

    protected $connection = 'central';

    protected $dispatchesEvents = [
        'saving' => Events\SavingTenant::class,
        'saved' => Events\TenantSaved::class,
        'creating' => Events\CreatingTenant::class,
        'created' => Events\TenantCreated::class,
        'updating' => Events\UpdatingTenant::class,
        'updated' => Events\TenantUpdated::class,
        'deleting' => Events\DeletingTenant::class,
        'deleted' => Events\TenantDeleted::class,
    ];

    protected $hidden = [
        'database_password',
    ];

    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'status',
        'country_code',
        'currency_code',
        'locale',
        'timezone',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'activated_at',
        'suspended_at',
        'archived_at',
        'meta',
        'database_password',
        'database_options',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'database_password' => 'encrypted',
            'database_options' => 'array',
            'meta' => 'array',
        ];
    }

    public function getTenantKeyName(): string
    {
        return $this->getKeyName();
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function getInternal(string $key)
    {
        return match ($key) {
            'db_name' => $this->database_name,
            'db_host' => $this->database_host,
            'db_port' => $this->database_port,
            'db_username' => $this->database_username,
            'db_password' => $this->database_password,
            'db_connection' => config('tenancy.database.template_tenant_connection', 'tenant_template'),
            default => data_get($this->meta ?? [], "tenancy.{$key}"),
        };
    }

    public function setInternal(string $key, $value)
    {
        switch ($key) {
            case 'db_name':
                $this->database_name = $value;
                break;
            case 'db_host':
                $this->database_host = $value;
                break;
            case 'db_port':
                $this->database_port = $value;
                break;
            case 'db_username':
                $this->database_username = $value;
                break;
            case 'db_password':
                $this->database_password = $value;
                break;
            case 'db_connection':
                break;
            default:
                $meta = $this->meta ?? [];
                data_set($meta, "tenancy.{$key}", $value);
                $this->meta = $meta;
                break;
        }

        return $this;
    }

    public function database(): DatabaseConfig
    {
        return new StanclTenantDatabaseConfig($this);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    public function platformUsers(): HasMany
    {
        return $this->hasMany(PlatformUser::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TenantStatusHistory::class)->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class)->latest();
    }

    public function featureOverrides(): HasMany
    {
        return $this->hasMany(TenantFeatureFlag::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(PlatformSupportTicket::class);
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class);
    }

    public function activeSubscription(): ?TenantSubscription
    {
        return $this->subscriptions
            ->first(fn (TenantSubscription $subscription) => in_array($subscription->status?->value ?? $subscription->status, ['active', 'trialing'], true))
            ?? $this->subscriptions()
                ->whereIn('status', ['active', 'trialing'])
                ->latest('id')
                ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? 'slug', $value)
            ->orWhere('public_id', $value)
            ->first();
    }
}
