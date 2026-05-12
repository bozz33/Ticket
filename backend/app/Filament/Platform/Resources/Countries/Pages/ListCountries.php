<?php

namespace App\Filament\Platform\Resources\Countries\Pages;

use App\Filament\Platform\Resources\Countries\CountryResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListCountries extends ListRecordsPage
{
    protected static string $resource = CountryResource::class;
}
