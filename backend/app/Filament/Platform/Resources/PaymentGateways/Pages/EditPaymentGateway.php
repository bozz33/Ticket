<?php

namespace App\Filament\Platform\Resources\PaymentGateways\Pages;

use App\Filament\Platform\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGateway extends EditRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    protected string|null $maxWidth = '7xl';
}
