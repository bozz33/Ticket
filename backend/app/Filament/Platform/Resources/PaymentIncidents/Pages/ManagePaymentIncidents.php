<?php

namespace App\Filament\Platform\Resources\PaymentIncidents\Pages;

use App\Filament\Platform\Resources\PaymentIncidents\PaymentIncidentResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

class ManagePaymentIncidents extends ManageRecordsPage
{
    protected static string $resource = PaymentIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
