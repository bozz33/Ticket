<?php

namespace App\Filament\Platform\Resources\Roles\Pages;

use App\Filament\Platform\Resources\Roles\RoleResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateRole extends CreateRecordPage
{
    protected static string $resource = RoleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
