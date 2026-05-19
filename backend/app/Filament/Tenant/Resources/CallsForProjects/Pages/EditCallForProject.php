<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use App\Models\OrganizationProfile;
use Filament\Support\Enums\Width;

class EditCallForProject extends EditRecordPage
{
    protected static string $resource = CallForProjectResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_profile_id'] ??= OrganizationProfile::query()->value('id');

        return $data;
    }
}
