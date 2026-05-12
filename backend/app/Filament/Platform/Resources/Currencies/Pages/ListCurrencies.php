<?php

namespace App\Filament\Platform\Resources\Currencies\Pages;

use App\Filament\Platform\Resources\Currencies\CurrencyResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListCurrencies extends ListRecordsPage
{
    protected static string $resource = CurrencyResource::class;
}
