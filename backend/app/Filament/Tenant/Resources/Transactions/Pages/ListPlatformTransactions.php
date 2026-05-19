<?php

namespace App\Filament\Tenant\Resources\Transactions\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Transactions\PlatformTransactionResource;

class ListPlatformTransactions extends ListRecordsPage
{
    protected static string $resource = PlatformTransactionResource::class;
}
