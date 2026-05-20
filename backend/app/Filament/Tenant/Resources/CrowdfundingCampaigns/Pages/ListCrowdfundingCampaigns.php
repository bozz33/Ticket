<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use Filament\Actions\CreateAction;

class ListCrowdfundingCampaigns extends ListRecordsPage
{
    protected static string $resource = CrowdfundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
