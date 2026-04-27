<?php

namespace App\Filament\Platform\Resources\PaymentGateways\Pages;

use App\Filament\Platform\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentGateways extends ManageRecords
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
