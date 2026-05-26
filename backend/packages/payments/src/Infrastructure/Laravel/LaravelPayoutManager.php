<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\PayoutPolicy;
use App\Models\Tenant;
use Ticket\FinanceAccounting\Contracts\PayoutPolicyCatalog;
use Ticket\Payments\Contracts\PayoutManager;

class LaravelPayoutManager implements PayoutManager
{
    public function __construct(
        private readonly PayoutPolicyCatalog $payouts,
    ) {}

    public function resolvePolicy(Tenant $tenant, ?string $currencyCode = null): ?PayoutPolicy
    {
        return $this->payouts->resolvePolicy($tenant, $currencyCode);
    }

    public function computePayout(Tenant $tenant, int $grossAmount, ?string $currencyCode = null): array
    {
        return $this->payouts->computePayout($tenant, $grossAmount, $currencyCode);
    }

    public function availableBalance(Tenant $tenant, ?string $currencyCode = null): array
    {
        return $this->payouts->availableBalance($tenant, $currencyCode);
    }
}
