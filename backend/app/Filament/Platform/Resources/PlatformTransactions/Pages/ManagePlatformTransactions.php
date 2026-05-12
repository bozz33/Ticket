<?php

namespace App\Filament\Platform\Resources\PlatformTransactions\Pages;

use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManagePlatformTransactions extends ManageRecordsPage
{
    protected static string $resource = PlatformTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
