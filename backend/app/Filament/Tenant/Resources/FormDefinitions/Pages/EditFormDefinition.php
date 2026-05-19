<?php

namespace App\Filament\Tenant\Resources\FormDefinitions\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\FormDefinitions\FormDefinitionResource;
use Filament\Support\Enums\Width;

class EditFormDefinition extends EditRecordPage
{
    protected static string $resource = FormDefinitionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
