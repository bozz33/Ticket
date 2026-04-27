<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use Filament\Resources\Pages\EditRecord;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected string|null $maxWidth = '7xl';
}
