<?php

namespace App\Filament\Platform\Resources\Plans\Pages;

use App\Filament\Platform\Resources\Plans\PlanResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPlan extends EditRecordPage
{
    protected static string $resource = PlanResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
