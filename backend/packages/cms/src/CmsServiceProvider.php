<?php

namespace Ticket\Cms;

use Illuminate\Support\ServiceProvider;
use Ticket\Cms\Contracts\FrontCmsContent;
use Ticket\Cms\Infrastructure\Laravel\FrontCmsService;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FrontCmsContent::class, FrontCmsService::class);
    }
}
