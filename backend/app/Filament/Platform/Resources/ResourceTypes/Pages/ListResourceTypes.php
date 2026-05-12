<?php

namespace App\Filament\Platform\Resources\ResourceTypes\Pages;

use App\Filament\Platform\Resources\ResourceTypes\ResourceTypeResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListResourceTypes extends ListRecordsPage
{
    protected static string $resource = ResourceTypeResource::class;
}
