<?php

namespace App\Filament\Platform\Resources\PaymentGateways\Pages;

use App\Filament\Platform\Resources\PaymentGateways\PaymentGatewayResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePaymentGateway extends CreateRecordPage
{
    protected static string $resource = PaymentGatewayResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
