<?php

namespace App\Filament\Tenant\Resources\Events\Pages;

use App\Filament\Tenant\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

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
