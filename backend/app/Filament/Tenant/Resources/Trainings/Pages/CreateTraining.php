<?php

namespace App\Filament\Tenant\Resources\Trainings\Pages;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateTraining extends CreateRecordPage
{
    protected static string $resource = TrainingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
