<?php

namespace App\Filament\Platform\Resources\PaymentIncidents\Pages;

use App\Filament\Platform\Resources\PaymentIncidents\PaymentIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentIncidents extends ManageRecords
{
    protected static string $resource = PaymentIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
