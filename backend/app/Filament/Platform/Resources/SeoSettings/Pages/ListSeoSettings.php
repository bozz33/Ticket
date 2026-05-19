<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use App\Filament\Support\Pages\ListRecordsPage;
use Filament\Actions\CreateAction;

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
