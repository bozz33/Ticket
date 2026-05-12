<?php

namespace App\Filament\Tenant\Resources\Events\Pages;

use App\Filament\Tenant\Resources\Events\EventResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateEvent extends CreateRecordPage
{
    protected static string $resource = EventResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
