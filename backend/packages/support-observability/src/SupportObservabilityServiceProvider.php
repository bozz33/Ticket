<?php

namespace Ticket\SupportObservability;

use Illuminate\Support\ServiceProvider;
use Ticket\SupportObservability\Application\AuditService;
use Ticket\SupportObservability\Contracts\AuditLogger;

class SupportObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, AuditService::class);
    }
}
