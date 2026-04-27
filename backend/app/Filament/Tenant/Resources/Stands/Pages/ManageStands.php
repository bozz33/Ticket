<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Tenant\Resources\Stands\StandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStands extends ManageRecords
{
    protected static string $resource = StandResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
