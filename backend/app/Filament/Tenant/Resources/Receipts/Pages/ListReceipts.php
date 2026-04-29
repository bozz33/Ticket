<?php

namespace App\Filament\Tenant\Resources\Receipts\Pages;

use App\Filament\Tenant\Resources\Receipts\ReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;
}
