<?php

namespace App\Filament\Platform\Resources\Roles\Pages;

use App\Filament\Platform\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListRoles extends ListRecordsPage
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
