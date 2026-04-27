<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCallForProjects extends ManageRecords
{
    protected static string $resource = CallForProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
