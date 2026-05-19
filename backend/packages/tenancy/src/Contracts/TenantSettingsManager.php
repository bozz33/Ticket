<?php

namespace Ticket\Tenancy\Contracts;

use Illuminate\Support\Collection;

interface TenantSettingsManager
{
    public function list(?string $group = null): Collection;

    public function upsertMany(array $items): Collection;
}
