<?php

namespace Ticket\ContentCallsForProjects\Tests\Unit;

use Tests\TestCase;
use Ticket\ContentCallsForProjects\Contracts\CallForProjectContentCatalog;

class ContentCallsForProjectsBindingsTest extends TestCase
{
    public function test_call_for_project_content_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(CallForProjectContentCatalog::class, app(CallForProjectContentCatalog::class));
    }
}
