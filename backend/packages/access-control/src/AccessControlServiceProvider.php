<?php

namespace Ticket\AccessControl;

use Illuminate\Support\ServiceProvider;
use Ticket\AccessControl\Application\AccessPassCheckinService;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;

class AccessControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccessPassCheckinWorkflow::class, AccessPassCheckinService::class);
    }
}
