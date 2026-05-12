<?php

namespace App\Filament\Platform\Resources\PublicStatuses\Pages;

use App\Filament\Platform\Resources\PublicStatuses\PublicStatusResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPublicStatuses extends ListRecordsPage
{
    protected static string $resource = PublicStatusResource::class;
}
