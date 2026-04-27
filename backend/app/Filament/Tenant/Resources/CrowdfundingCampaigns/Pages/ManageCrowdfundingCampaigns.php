<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages;

use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrowdfundingCampaigns extends ManageRecords
{
    protected static string $resource = CrowdfundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
