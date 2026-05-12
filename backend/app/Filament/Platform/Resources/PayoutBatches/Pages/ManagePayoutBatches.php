<?php

namespace App\Filament\Platform\Resources\PayoutBatches\Pages;

use App\Filament\Platform\Resources\PayoutBatches\PayoutBatchResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManagePayoutBatches extends ManageRecordsPage
{
    protected static string $resource = PayoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
