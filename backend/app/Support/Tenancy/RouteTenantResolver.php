<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedByPathException;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;

class RouteTenantResolver extends CachedTenantResolver
{
    public static string $tenantParameterName = 'tenant';

    public function resolveWithoutCache(...$args): Tenant
    {
        /** @var Route $route */
        $route = $args[0];
        $parameter = $route->parameter(static::$tenantParameterName);

        if ($parameter instanceof Tenant) {
            return $parameter;
        }

        $tenantModel = config('tenancy.tenant_model');
        $tenant = app($tenantModel)
            ->newQuery()
            ->where(function (Builder $query) use ($parameter): void {
                if (is_numeric($parameter)) {
                    $query->whereKey($parameter);
                }

                if (is_string($parameter) && Str::isUuid($parameter)) {
                    $query->orWhere('public_id', $parameter);
                }

                $query->orWhere('slug', $parameter);
            })
            ->first();

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        throw new TenantCouldNotBeIdentifiedByPathException($parameter);
    }

    public function resolved(Tenant $tenant, ...$args): void
    {
        /** @var Route $route */
        $route = $args[0];
    }

    public function getArgsForTenant(Tenant $tenant): array
    {
        return array_filter([
            [$tenant->getTenantKey()],
            [data_get($tenant, 'public_id')],
            [data_get($tenant, 'slug')],
        ], fn (array $args): bool => filled($args[0] ?? null));
    }
}
