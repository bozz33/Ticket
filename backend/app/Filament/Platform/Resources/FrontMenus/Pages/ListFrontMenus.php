<?php

namespace App\Filament\Platform\Resources\FrontMenus\Pages;

use App\Filament\Platform\Resources\FrontMenus\FrontMenuResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListFrontMenus extends ListRecordsPage
{
    protected static string $resource = FrontMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
