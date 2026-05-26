<?php

namespace Ticket\ContentCallsForProjects;

use Illuminate\Support\ServiceProvider;
use Ticket\ContentCallsForProjects\Application\CallForProjectService;
use Ticket\ContentCallsForProjects\Contracts\CallForProjectContentCatalog;

class ContentCallsForProjectsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CallForProjectContentCatalog::class, CallForProjectService::class);
    }
}
