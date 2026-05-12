<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticatePlatformApi;
use App\Http\Middleware\AuthenticateTenantApi;
use App\Http\Middleware\EnsureTenantCategoriesAreSynced;
use App\Http\Middleware\EnsureTenantSubscriptionIsActive;
use App\Http\Middleware\InitializeTenancyByRouteParameter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ValidateSignature;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.platform.api' => AuthenticatePlatformApi::class,
            'auth.tenant.api' => AuthenticateTenantApi::class,
            'initialize.tenant.route' => InitializeTenancyByRouteParameter::class,
            'tenant.categories.synced' => EnsureTenantCategoriesAreSynced::class,
            'tenant.subscription.active' => EnsureTenantSubscriptionIsActive::class,
            'signed' => ValidateSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })
    ->create();
