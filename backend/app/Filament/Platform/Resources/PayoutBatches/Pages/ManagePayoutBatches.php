<?php

namespace App\Filament\Platform\Resources\PayoutBatches\Pages;

use App\Filament\Platform\Resources\PayoutBatches\PayoutBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePayoutBatches extends ManageRecords
{
    protected static string $resource = PayoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
