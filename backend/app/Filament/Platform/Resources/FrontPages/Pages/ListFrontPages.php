<?php

namespace App\Filament\Platform\Resources\FrontPages\Pages;

use App\Filament\Platform\Resources\FrontPages\FrontPageResource;
use App\Filament\Support\Pages\ListRecordsPage;
use Filament\Actions\CreateAction;

class ListFrontPages extends ListRecordsPage
{
    protected static string $resource = FrontPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
