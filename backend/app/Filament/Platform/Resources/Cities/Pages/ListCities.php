<?php

namespace App\Filament\Platform\Resources\Cities\Pages;

use App\Filament\Platform\Resources\Cities\CityResource;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;
}
