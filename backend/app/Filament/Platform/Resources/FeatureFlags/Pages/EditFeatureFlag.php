<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditFeatureFlag extends EditRecordPage
{
    protected static string $resource = FeatureFlagResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
