<?php

namespace App\Filament\Tenant\Resources\Receipts\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Receipts\ReceiptResource;

class ListReceipts extends ListRecordsPage
{
    protected static string $resource = ReceiptResource::class;
}
