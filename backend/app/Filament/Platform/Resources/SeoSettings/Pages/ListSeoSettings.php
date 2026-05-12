<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListSeoSettings extends ListRecordsPage
{
    protected static string $resource = SeoSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
