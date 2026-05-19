<?php

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Users\UserResource;
use Filament\Actions\CreateAction;

class ListUsers extends ListRecordsPage
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
