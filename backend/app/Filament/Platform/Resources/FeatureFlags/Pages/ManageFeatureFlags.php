<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageFeatureFlags extends ManageRecordsPage
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
