<?php

namespace Ticket\ContentEvents\Tests\Unit;

use Tests\TestCase;
use Ticket\ContentEvents\Contracts\EventContentCatalog;

class ContentEventsBindingsTest extends TestCase
{
    public function test_event_content_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(EventContentCatalog::class, app(EventContentCatalog::class));
    }
}
