<?php

namespace App\Filament\Platform\Resources\PaymentGateways\Pages;

use App\Filament\Platform\Resources\PaymentGateways\PaymentGatewayResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPaymentGateway extends EditRecordPage
{
    protected static string $resource = PaymentGatewayResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
