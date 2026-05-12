<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateFeatureFlag extends CreateRecordPage
{
    protected static string $resource = FeatureFlagResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
