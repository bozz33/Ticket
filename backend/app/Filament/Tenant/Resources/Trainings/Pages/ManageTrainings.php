<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrainings extends ManageRecords
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
