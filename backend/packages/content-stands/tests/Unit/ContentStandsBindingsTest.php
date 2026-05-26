<?php

namespace Ticket\ContentStands\Tests\Unit;

use Tests\TestCase;
use Ticket\ContentStands\Contracts\StandContentCatalog;

class ContentStandsBindingsTest extends TestCase
{
    public function test_stand_content_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(StandContentCatalog::class, app(StandContentCatalog::class));
    }
}
