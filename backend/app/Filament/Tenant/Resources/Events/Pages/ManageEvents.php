<?php

namespace App\Filament\Tenant\Resources\Events\Pages;

use App\Filament\Support\Pages\ManageRecordsPage;
use App\Filament\Tenant\Resources\Events\EventResource;
use Filament\Actions\CreateAction;

class ManageEvents extends ManageRecordsPage
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
