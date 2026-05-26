<?php

namespace Ticket\Cms\Tests\Unit;

use Tests\TestCase;
use Ticket\Cms\Contracts\FrontCmsContent;

class CmsBindingsTest extends TestCase
{
    public function test_cms_contracts_are_bound(): void
    {
        $this->assertInstanceOf(FrontCmsContent::class, app(FrontCmsContent::class));
    }
}
