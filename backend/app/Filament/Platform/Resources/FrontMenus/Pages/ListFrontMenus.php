<?php

namespace App\Filament\Platform\Resources\FrontMenus\Pages;

use App\Filament\Platform\Resources\FrontMenus\FrontMenuResource;
use App\Filament\Support\Pages\ListRecordsPage;
use Filament\Actions\CreateAction;

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
