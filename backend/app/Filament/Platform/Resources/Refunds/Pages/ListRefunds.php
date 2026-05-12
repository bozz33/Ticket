<?php

namespace App\Filament\Platform\Resources\Refunds\Pages;

use App\Filament\Platform\Resources\Refunds\RefundResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListRefunds extends ListRecordsPage
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
