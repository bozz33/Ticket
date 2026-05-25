<?php

namespace App\Filament\Platform\Resources\TranslationEntries\Pages;

use App\Filament\Platform\Resources\TranslationEntries\TranslationEntryResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListTranslationEntries extends ListRecordsPage
{
    protected static string $resource = TranslationEntryResource::class;
}
