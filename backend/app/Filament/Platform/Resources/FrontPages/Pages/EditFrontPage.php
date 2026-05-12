<?php

namespace App\Filament\Platform\Resources\FrontPages\Pages;

use App\Filament\Platform\Resources\FrontPages\FrontPageResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditFrontPage extends EditRecordPage
{
    protected static string $resource = FrontPageResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
