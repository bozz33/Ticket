<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages;

use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditCrowdfundingCampaign extends EditRecordPage
{
    protected static string $resource = CrowdfundingCampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
