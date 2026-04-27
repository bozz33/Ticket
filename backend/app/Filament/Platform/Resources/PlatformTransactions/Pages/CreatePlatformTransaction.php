<?php

namespace App\Filament\Platform\Resources\PlatformTransactions\Pages;

use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformTransaction extends CreateRecord
{
    protected static string $resource = PlatformTransactionResource::class;

    protected string|null $maxWidth = '7xl';
}
