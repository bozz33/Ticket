<?php

namespace App\Filament\Platform\Resources\PublicStatuses\Pages;

use App\Filament\Platform\Resources\PublicStatuses\PublicStatusResource;
use Filament\Resources\Pages\ListRecords;

class ListPublicStatuses extends ListRecords
{
    protected static string $resource = PublicStatusResource::class;
}
