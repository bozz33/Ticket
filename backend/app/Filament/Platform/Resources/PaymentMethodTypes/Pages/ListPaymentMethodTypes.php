<?php

namespace App\Filament\Platform\Resources\PaymentMethodTypes\Pages;

use App\Filament\Platform\Resources\PaymentMethodTypes\PaymentMethodTypeResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPaymentMethodTypes extends ListRecordsPage
{
    protected static string $resource = PaymentMethodTypeResource::class;
}
