<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageSeoSettings extends ManageRecordsPage
{
    protected static string $resource = SeoSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
