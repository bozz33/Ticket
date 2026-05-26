<?php

namespace Ticket\Seo\Tests\Unit;

use Tests\TestCase;
use Ticket\Seo\Contracts\SeoMetadataCatalog;

class SeoBindingsTest extends TestCase
{
    public function test_seo_metadata_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(SeoMetadataCatalog::class, app(SeoMetadataCatalog::class));
    }
}
