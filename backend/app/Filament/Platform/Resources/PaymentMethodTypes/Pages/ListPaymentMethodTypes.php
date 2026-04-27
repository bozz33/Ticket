<?php

namespace App\Filament\Platform\Resources\PaymentMethodTypes\Pages;

use App\Filament\Platform\Resources\PaymentMethodTypes\PaymentMethodTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethodTypes extends ListRecords
{
    protected static string $resource = PaymentMethodTypeResource::class;
}
