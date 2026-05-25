<?php

namespace App\Filament\Tenant\Resources\RefundRequests\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\RefundRequests\RefundRequestResource;

class ListRefundRequests extends ListRecordsPage
{
    protected static string $resource = RefundRequestResource::class;
}
