<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages;

use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageCrowdfundingCampaigns extends ManageRecordsPage
{
    protected static string $resource = CrowdfundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
