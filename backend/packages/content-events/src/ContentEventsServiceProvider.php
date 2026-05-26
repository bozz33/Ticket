<?php

namespace Ticket\ContentEvents;

use Illuminate\Support\ServiceProvider;
use Ticket\ContentEvents\Application\EventService;
use Ticket\ContentEvents\Contracts\EventContentCatalog;

class ContentEventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventContentCatalog::class, EventService::class);
    }
}
