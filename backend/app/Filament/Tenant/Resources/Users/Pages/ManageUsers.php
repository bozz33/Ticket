<?php

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Support\Pages\ManageRecordsPage;
use App\Filament\Tenant\Resources\Users\UserResource;
use Filament\Actions\CreateAction;

class ManageUsers extends ManageRecordsPage
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
