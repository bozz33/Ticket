<?php

namespace Ticket\ContentStands;

use Illuminate\Support\ServiceProvider;
use Ticket\ContentStands\Application\StandService;
use Ticket\ContentStands\Contracts\StandContentCatalog;

class ContentStandsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StandContentCatalog::class, StandService::class);
    }
}
