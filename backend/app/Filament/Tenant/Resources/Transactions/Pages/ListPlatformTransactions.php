<?php

namespace App\Filament\Tenant\Resources\Transactions\Pages;

use App\Filament\Tenant\Resources\Transactions\PlatformTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformTransactions extends ListRecords
{
    protected static string $resource = PlatformTransactionResource::class;
}
