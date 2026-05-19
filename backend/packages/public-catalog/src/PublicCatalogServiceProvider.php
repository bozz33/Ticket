<?php

namespace Ticket\PublicCatalog;

use Illuminate\Support\ServiceProvider;
use Ticket\PublicCatalog\Application\PublicCatalogProjectionReader;
use Ticket\PublicCatalog\Application\PublicCatalogProjector;
use Ticket\PublicCatalog\Contracts\CallForProjectApplications;
use Ticket\PublicCatalog\Contracts\CallForProjectFormBuilder;
use Ticket\PublicCatalog\Contracts\FrontContent;
use Ticket\PublicCatalog\Contracts\PublicContentCatalog;
use Ticket\PublicCatalog\Infrastructure\Laravel\LaravelCallForProjectApplications;
use Ticket\PublicCatalog\Infrastructure\Laravel\LaravelCallForProjectFormBuilder;
use Ticket\PublicCatalog\Infrastructure\Laravel\LaravelFrontContent;
use Ticket\PublicCatalog\Infrastructure\Laravel\LaravelPublicContentCatalog;

class PublicCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PublicCatalogProjectionReader::class);
        $this->app->singleton(PublicCatalogProjector::class);
        $this->app->bind(PublicContentCatalog::class, LaravelPublicContentCatalog::class);
        $this->app->bind(FrontContent::class, LaravelFrontContent::class);
        $this->app->bind(CallForProjectFormBuilder::class, LaravelCallForProjectFormBuilder::class);
        $this->app->bind(CallForProjectApplications::class, LaravelCallForProjectApplications::class);
    }
}
