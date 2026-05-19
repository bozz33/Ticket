<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use Illuminate\Support\Collection;
use Ticket\Tenancy\Application\TenantSettingsService;
use Ticket\Tenancy\Contracts\TenantSettingsManager;

class LaravelTenantSettingsManager implements TenantSettingsManager
{
    public function __construct(private readonly TenantSettingsService $settings) {}

    public function list(?string $group = null): Collection
    {
        return $this->settings->list($group);
    }

    public function upsertMany(array $items): Collection
    {
        return $this->settings->upsertMany($items);
    }
}
