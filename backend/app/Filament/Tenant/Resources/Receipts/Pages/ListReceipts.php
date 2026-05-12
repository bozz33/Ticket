<?php

namespace App\Filament\Tenant\Resources\Receipts\Pages;

use App\Filament\Tenant\Resources\Receipts\ReceiptResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListReceipts extends ListRecordsPage
{
    protected static string $resource = ReceiptResource::class;
}
