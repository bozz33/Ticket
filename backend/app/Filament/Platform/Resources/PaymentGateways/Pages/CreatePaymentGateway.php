<?php

namespace App\Filament\Platform\Resources\PaymentGateways\Pages;

use App\Filament\Platform\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentGateway extends CreateRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    protected string|null $maxWidth = '7xl';
}
