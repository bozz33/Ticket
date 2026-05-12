<?php

namespace App\Filament\Support\Pages;

use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Support\Enums\Width;

abstract class ListRecordsPage extends BaseListRecords
{
    protected Width|string|null $maxContentWidth = Width::Full;
}
