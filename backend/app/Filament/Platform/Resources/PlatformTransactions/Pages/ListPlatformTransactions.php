<?php

namespace App\Filament\Platform\Resources\PlatformTransactions\Pages;

use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPlatformTransactions extends ListRecordsPage
{
    protected static string $resource = PlatformTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
