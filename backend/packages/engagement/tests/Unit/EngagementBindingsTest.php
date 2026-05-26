<?php

namespace Ticket\Engagement\Tests\Unit;

use Tests\TestCase;
use Ticket\Engagement\Contracts\EventEngagementWorkflow;
use Ticket\Engagement\Contracts\OrganizationAudienceWorkflow;

class EngagementBindingsTest extends TestCase
{
    public function test_engagement_contracts_are_bound(): void
    {
        $this->assertInstanceOf(EventEngagementWorkflow::class, app(EventEngagementWorkflow::class));
        $this->assertInstanceOf(OrganizationAudienceWorkflow::class, app(OrganizationAudienceWorkflow::class));
    }
}
