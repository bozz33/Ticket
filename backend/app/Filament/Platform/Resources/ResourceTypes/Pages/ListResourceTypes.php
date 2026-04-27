<?php

namespace App\Filament\Platform\Resources\ResourceTypes\Pages;

use App\Filament\Platform\Resources\ResourceTypes\ResourceTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListResourceTypes extends ListRecords
{
    protected static string $resource = ResourceTypeResource::class;
}
