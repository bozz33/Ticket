<?php

namespace App\Filament\Platform\Resources\Plans\Pages;

use App\Filament\Platform\Resources\Plans\PlanResource;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected string|null $maxWidth = '7xl';
}
