<?php

namespace App\Filament\Platform\Resources\Currencies\Pages;

use App\Filament\Platform\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;
}
