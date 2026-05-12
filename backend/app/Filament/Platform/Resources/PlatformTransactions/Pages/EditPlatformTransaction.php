<?php

namespace App\Filament\Platform\Resources\PlatformTransactions\Pages;

use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPlatformTransaction extends EditRecordPage
{
    protected static string $resource = PlatformTransactionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
