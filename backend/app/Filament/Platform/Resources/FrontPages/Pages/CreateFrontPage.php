<?php

namespace App\Filament\Platform\Resources\FrontPages\Pages;

use App\Filament\Platform\Resources\FrontPages\FrontPageResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateFrontPage extends CreateRecordPage
{
    protected static string $resource = FrontPageResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
