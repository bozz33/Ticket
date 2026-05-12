<?php

namespace App\Filament\Platform\Resources\GatewayWebhookLogs\Pages;

use App\Filament\Platform\Resources\GatewayWebhookLogs\GatewayWebhookLogResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageGatewayWebhookLogs extends ManageRecordsPage
{
    protected static string $resource = GatewayWebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
