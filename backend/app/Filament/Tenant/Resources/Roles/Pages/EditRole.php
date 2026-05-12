<?php

namespace App\Filament\Tenant\Resources\Roles\Pages;

use App\Filament\Tenant\Resources\Roles\RoleResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditRole extends EditRecordPage
{
    protected static string $resource = RoleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
