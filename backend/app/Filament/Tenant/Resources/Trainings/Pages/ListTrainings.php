<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use Filament\Actions\CreateAction;

class ListTrainings extends ListRecordsPage
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
