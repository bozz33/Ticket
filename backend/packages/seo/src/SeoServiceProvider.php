<?php

namespace Ticket\Seo;

use Illuminate\Support\ServiceProvider;
use Ticket\Seo\Contracts\SeoMetadataCatalog;
use Ticket\Seo\Infrastructure\Laravel\LaravelSeoMetadataCatalog;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoMetadataCatalog::class, LaravelSeoMetadataCatalog::class);
    }
}
