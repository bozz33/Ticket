<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Support\Pages\ManageRecordsPage;
use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use Filament\Actions\CreateAction;

class ManageCallForProjects extends ManageRecordsPage
{
    protected static string $resource = CallForProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
