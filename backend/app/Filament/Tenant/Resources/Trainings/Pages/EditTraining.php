<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditTraining extends EditRecordPage
{
    protected static string $resource = TrainingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
