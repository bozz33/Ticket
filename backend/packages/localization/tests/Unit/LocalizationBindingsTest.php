<?php

namespace Ticket\Localization\Tests\Unit;

use Tests\TestCase;
use Ticket\Localization\Contracts\PublicLocalizationCatalog;

class LocalizationBindingsTest extends TestCase
{
    public function test_localization_contracts_are_bound(): void
    {
        $this->assertInstanceOf(PublicLocalizationCatalog::class, app(PublicLocalizationCatalog::class));
    }
}
