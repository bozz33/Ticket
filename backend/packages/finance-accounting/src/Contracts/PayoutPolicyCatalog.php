<?php

namespace Ticket\FinanceAccounting\Contracts;

use App\Models\PayoutPolicy;
use App\Models\Tenant;

interface PayoutPolicyCatalog
{
    public function resolvePolicy(Tenant $tenant, ?string $currencyCode = null): ?PayoutPolicy;

    public function computePayout(Tenant $tenant, int $grossAmount, ?string $currencyCode = null): array;

    public function availableBalance(Tenant $tenant, ?string $currencyCode = null): array;
}
