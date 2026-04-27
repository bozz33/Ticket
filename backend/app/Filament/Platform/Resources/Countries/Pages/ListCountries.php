<?php

namespace App\Filament\Platform\Resources\Countries\Pages;

use App\Filament\Platform\Resources\Countries\CountryResource;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;
}
