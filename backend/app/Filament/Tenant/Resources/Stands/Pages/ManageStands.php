<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Support\Pages\ManageRecordsPage;
use App\Filament\Tenant\Resources\Stands\StandResource;
use Filament\Actions\CreateAction;

class ManageStands extends ManageRecordsPage
{
    protected static string $resource = StandResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
