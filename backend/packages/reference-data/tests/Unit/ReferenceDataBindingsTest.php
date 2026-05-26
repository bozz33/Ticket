<?php

namespace Ticket\ReferenceData\Tests\Unit;

use Tests\TestCase;
use Ticket\ReferenceData\Contracts\CityReferenceSearch;
use Ticket\ReferenceData\Contracts\CountryReferenceImport;

class ReferenceDataBindingsTest extends TestCase
{
    public function test_reference_data_contracts_are_bound(): void
    {
        $this->assertInstanceOf(CountryReferenceImport::class, app(CountryReferenceImport::class));
        $this->assertInstanceOf(CityReferenceSearch::class, app(CityReferenceSearch::class));
    }
}
