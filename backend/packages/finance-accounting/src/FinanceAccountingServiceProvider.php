<?php

namespace Ticket\FinanceAccounting;

use Illuminate\Support\ServiceProvider;
use Ticket\FinanceAccounting\Application\FinancePolicyService;
use Ticket\FinanceAccounting\Application\PayoutPolicyService;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\FinanceAccounting\Contracts\PayoutPolicyCatalog;

class FinanceAccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancePolicyCatalog::class, FinancePolicyService::class);
        $this->app->bind(PayoutPolicyCatalog::class, PayoutPolicyService::class);
    }
}
