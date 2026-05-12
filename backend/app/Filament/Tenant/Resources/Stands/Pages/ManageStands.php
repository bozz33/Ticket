<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Tenant\Resources\Stands\StandResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageStands extends ManageRecordsPage
{
    protected static string $resource = StandResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
