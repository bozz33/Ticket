<?php

namespace App\Filament\Platform\Resources\FeatureFlags\Pages;

use App\Filament\Platform\Resources\FeatureFlags\FeatureFlagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFeatureFlags extends ManageRecords
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
