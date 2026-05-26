<?php

namespace Ticket\IdentityAccess;

use Illuminate\Support\ServiceProvider;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;
use Ticket\IdentityAccess\Infrastructure\Laravel\LaravelPlatformTokenService;
use Ticket\IdentityAccess\Infrastructure\Laravel\LaravelTenantTokenService;

class IdentityAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlatformTokenIssuer::class, LaravelPlatformTokenService::class);
        $this->app->bind(TenantTokenIssuer::class, LaravelTenantTokenService::class);
    }
}
