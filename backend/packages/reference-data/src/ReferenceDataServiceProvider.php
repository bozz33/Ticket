<?php

namespace Ticket\ReferenceData;

use Illuminate\Support\ServiceProvider;
use Ticket\ReferenceData\Contracts\CityReferenceSearch;
use Ticket\ReferenceData\Contracts\CountryReferenceImport;
use Ticket\ReferenceData\Infrastructure\Laravel\CityReferenceSearchService;
use Ticket\ReferenceData\Infrastructure\Laravel\CountryReferenceImporter;

class ReferenceDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CountryReferenceImport::class, CountryReferenceImporter::class);
        $this->app->bind(CityReferenceSearch::class, CityReferenceSearchService::class);
    }
}
