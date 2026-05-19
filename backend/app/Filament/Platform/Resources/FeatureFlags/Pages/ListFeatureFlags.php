<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use App\Filament\Support\Pages\ListRecordsPage;
use Filament\Actions\CreateAction;

class ListFeatureFlags extends ListRecordsPage
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
