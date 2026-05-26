<?php

namespace Ticket\ContentCrowdfunding\Tests\Unit;

use Tests\TestCase;
use Ticket\ContentCrowdfunding\Contracts\CrowdfundingContentCatalog;

class ContentCrowdfundingBindingsTest extends TestCase
{
    public function test_crowdfunding_content_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(CrowdfundingContentCatalog::class, app(CrowdfundingContentCatalog::class));
    }
}
