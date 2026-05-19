<?php

namespace App\Filament\Tenant\Resources\FormDefinitions\Pages;

use App\Filament\Support\Pages\CreateRecordPage;
use App\Filament\Tenant\Resources\FormDefinitions\FormDefinitionResource;
use Filament\Support\Enums\Width;

class CreateFormDefinition extends CreateRecordPage
{
    protected static string $resource = FormDefinitionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
