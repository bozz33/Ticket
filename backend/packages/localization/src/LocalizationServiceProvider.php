<?php

namespace Ticket\Localization;

use Illuminate\Support\ServiceProvider;
use Ticket\Localization\Contracts\PublicLocalizationCatalog;
use Ticket\Localization\Infrastructure\Laravel\LaravelPublicLocalizationCatalog;

class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PublicLocalizationCatalog::class, LaravelPublicLocalizationCatalog::class);
    }
}
