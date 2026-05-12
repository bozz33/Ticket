<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Tenant\Resources\Stands\StandResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateStand extends CreateRecordPage
{
    protected static string $resource = StandResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
