<?php

namespace App\Filament\Tenant\Resources\OrganizationProfiles\Pages;

use App\Filament\Tenant\Resources\OrganizationProfiles\OrganizationProfileResource;
use App\Models\OrganizationProfile;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrganizationProfiles extends ManageRecords
{
    protected static string $resource = OrganizationProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Créer le profil')
                ->visible(fn (): bool => OrganizationProfile::query()->count() === 0),
        ];
    }
}
