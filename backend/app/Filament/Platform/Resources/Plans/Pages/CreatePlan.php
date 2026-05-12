<?php

namespace App\Filament\Platform\Resources\Plans\Pages;

use App\Filament\Platform\Resources\Plans\PlanResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePlan extends CreateRecordPage
{
    protected static string $resource = PlanResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
