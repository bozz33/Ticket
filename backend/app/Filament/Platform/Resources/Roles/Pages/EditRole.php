<?php

namespace App\Filament\Platform\Resources\Roles\Pages;

use App\Filament\Platform\Resources\Roles\RoleResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditRole extends EditRecordPage
{
    protected static string $resource = RoleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
