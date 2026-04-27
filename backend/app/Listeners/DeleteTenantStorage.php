<?php

namespace App\Listeners;

use App\Services\Tenancy\TenantStorageManager;
use Stancl\Tenancy\Events\TenantDeleted;

class DeleteTenantStorage
{
    public function __construct(
        protected TenantStorageManager $tenantStorageManager,
    ) {}

    public function handle(TenantDeleted $event): void
    {
        $this->tenantStorageManager->delete($event->tenant);
    }
}
