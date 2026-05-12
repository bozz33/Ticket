<?php

namespace App\Filament\Tenant\Resources\Transactions\Pages;

use App\Filament\Tenant\Resources\Transactions\PlatformTransactionResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPlatformTransactions extends ListRecordsPage
{
    protected static string $resource = PlatformTransactionResource::class;
}
