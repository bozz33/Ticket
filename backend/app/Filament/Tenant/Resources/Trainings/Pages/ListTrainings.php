<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

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
