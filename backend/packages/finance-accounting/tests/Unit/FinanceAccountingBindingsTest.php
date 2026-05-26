<?php

namespace Ticket\FinanceAccounting\Tests\Unit;

use Tests\TestCase;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\FinanceAccounting\Contracts\PayoutPolicyCatalog;

class FinanceAccountingBindingsTest extends TestCase
{
    public function test_finance_policy_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(FinancePolicyCatalog::class, app(FinancePolicyCatalog::class));
    }

    public function test_payout_policy_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(PayoutPolicyCatalog::class, app(PayoutPolicyCatalog::class));
    }
}
