<?php

namespace App\Filament\Tenant\Resources\Roles\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;

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
