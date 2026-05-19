<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Orders\OrderResource;

class ListOrders extends ListRecordsPage
{
    protected static string $resource = OrderResource::class;
}
