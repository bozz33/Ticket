<?php

namespace App\Filament\Tenant\Resources\Roles\Pages;

use App\Filament\Support\Pages\CreateRecordPage;
use App\Filament\Tenant\Resources\Roles\RoleResource;
use Filament\Support\Enums\Width;

class CreateRole extends CreateRecordPage
{
    protected static string $resource = RoleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
