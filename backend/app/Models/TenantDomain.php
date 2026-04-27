<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\Domain as TenantDomainContract;
use Stancl\Tenancy\Database\Concerns\ConvertsDomainsToLowercase;
use Stancl\Tenancy\Database\Concerns\EnsuresDomainIsNotOccupied;
use Stancl\Tenancy\Database\Concerns\InvalidatesTenantsResolverCache;
use Stancl\Tenancy\Events;

class TenantDomain extends Model implements TenantDomainContract
{
    use ConvertsDomainsToLowercase, EnsuresDomainIsNotOccupied, HasFactory, InvalidatesTenantsResolverCache;

    protected $connection = 'central';

    protected $dispatchesEvents = [
        'saving' => Events\SavingDomain::class,
        'saved' => Events\DomainSaved::class,
        'creating' => Events\CreatingDomain::class,
        'created' => Events\DomainCreated::class,
        'updating' => Events\UpdatingDomain::class,
        'updated' => Events\DomainUpdated::class,
        'deleting' => Events\DeletingDomain::class,
        'deleted' => Events\DomainDeleted::class,
    ];

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
