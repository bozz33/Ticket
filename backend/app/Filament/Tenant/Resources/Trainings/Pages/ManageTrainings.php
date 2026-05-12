<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageTrainings extends ManageRecordsPage
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
