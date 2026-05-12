<?php

namespace App\Filament\Platform\Resources\Cities\Pages;

use App\Filament\Platform\Resources\Cities\CityResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListCities extends ListRecordsPage
{
    protected static string $resource = CityResource::class;
}
