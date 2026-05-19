<?php

namespace App\Filament\Platform\Resources\Refunds\Pages;

use App\Filament\Platform\Resources\Refunds\RefundResource;
use App\Filament\Support\Pages\ListRecordsPage;
use Filament\Actions\CreateAction;

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
