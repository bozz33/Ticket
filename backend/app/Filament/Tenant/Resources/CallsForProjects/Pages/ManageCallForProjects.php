<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageCallForProjects extends ManageRecordsPage
{
    protected static string $resource = CallForProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
