<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages;

use App\Filament\Support\Pages\CreateRecordPage;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use Filament\Support\Enums\Width;

class CreateCrowdfundingCampaign extends CreateRecordPage
{
    protected static string $resource = CrowdfundingCampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
