<?php

namespace App\Filament\Tenant\Resources\AccessPasses\Pages;

use App\Filament\Tenant\Resources\AccessPasses\AccessPassResource;
use Filament\Resources\Pages\ListRecords;

class ListAccessPasses extends ListRecords
{
    protected static string $resource = AccessPassResource::class;
}
