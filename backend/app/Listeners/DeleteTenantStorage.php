<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantDeleted;
use Ticket\Tenancy\Contracts\TenantStorage;

class DeleteTenantStorage
{
    public function __construct(
        protected TenantStorage $tenantStorage,
    ) {}

    public function handle(TenantDeleted $event): void
    {
        $this->tenantStorage->delete($event->tenant);
    }
}
