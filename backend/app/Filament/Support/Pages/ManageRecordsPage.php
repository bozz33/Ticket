<?php

namespace App\Filament\Support\Pages;

use Filament\Resources\Pages\ManageRecords as BaseManageRecords;
use Filament\Support\Enums\Width;

abstract class ManageRecordsPage extends BaseManageRecords
{
    protected Width|string|null $maxContentWidth = Width::Full;
}
