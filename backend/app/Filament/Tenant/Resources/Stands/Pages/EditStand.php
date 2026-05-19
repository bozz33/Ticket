<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\Stands\StandResource;
use Filament\Support\Enums\Width;

class EditStand extends EditRecordPage
{
    protected static string $resource = StandResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
