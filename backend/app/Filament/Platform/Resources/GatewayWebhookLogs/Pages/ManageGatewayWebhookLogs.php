<?php

namespace App\Filament\Platform\Resources\GatewayWebhookLogs\Pages;

use App\Filament\Platform\Resources\GatewayWebhookLogs\GatewayWebhookLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGatewayWebhookLogs extends ManageRecords
{
    protected static string $resource = GatewayWebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
