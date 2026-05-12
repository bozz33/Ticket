<?php

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Tenant\Resources\Users\UserResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateUser extends CreateRecordPage
{
    protected static string $resource = UserResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
