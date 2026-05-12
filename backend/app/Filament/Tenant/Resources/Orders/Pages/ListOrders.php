<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Tenant\Resources\Orders\OrderResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListOrders extends ListRecordsPage
{
    protected static string $resource = OrderResource::class;
}
