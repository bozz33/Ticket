<?php

namespace Ticket\Engagement;

use Illuminate\Support\ServiceProvider;
use Ticket\Engagement\Application\EventLikeService;
use Ticket\Engagement\Application\OrganizationFollowService;
use Ticket\Engagement\Contracts\EventEngagementWorkflow;
use Ticket\Engagement\Contracts\OrganizationAudienceWorkflow;

class EngagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventEngagementWorkflow::class, EventLikeService::class);
        $this->app->bind(OrganizationAudienceWorkflow::class, OrganizationFollowService::class);
    }
}
