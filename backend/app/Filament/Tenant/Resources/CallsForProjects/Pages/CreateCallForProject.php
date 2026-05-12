<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use App\Models\OrganizationProfile;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateCallForProject extends CreateRecordPage
{
    protected static string $resource = CallForProjectResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_profile_id'] ??= OrganizationProfile::query()->value('id');

        return $data;
    }
}
