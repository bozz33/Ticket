<?php

namespace Ticket\ContentCrowdfunding;

use Illuminate\Support\ServiceProvider;
use Ticket\ContentCrowdfunding\Application\CrowdfundingCampaignService;
use Ticket\ContentCrowdfunding\Contracts\CrowdfundingContentCatalog;

class ContentCrowdfundingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CrowdfundingContentCatalog::class, CrowdfundingCampaignService::class);
    }
}
