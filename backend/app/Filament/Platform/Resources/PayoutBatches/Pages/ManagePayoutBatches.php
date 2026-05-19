<?php

namespace App\Filament\Platform\Resources\PayoutBatches\Pages;

use App\Filament\Platform\Resources\PayoutBatches\PayoutBatchResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
