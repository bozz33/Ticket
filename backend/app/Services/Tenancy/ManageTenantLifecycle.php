<?php

namespace App\Services\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ManageTenantLifecycle
{
    public function activate(Tenant $tenant): Tenant
    {
        return $this->persist($tenant, [
            'status' => TenantStatus::Active,
            'activated_at' => $tenant->activated_at ?? now(),
            'suspended_at' => null,
            'archived_at' => null,
        ]);
    }

    public function suspend(Tenant $tenant): Tenant
    {
        return $this->persist($tenant, [
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function archive(Tenant $tenant): Tenant
    {
        return $this->persist($tenant, [
            'status' => TenantStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    protected function persist(Tenant $tenant, array $attributes): Tenant
    {
        return DB::connection(config('ticket.central_connection'))->transaction(function () use ($tenant, $attributes): Tenant {
            $fromStatus = $tenant->status;
            $tenant->forceFill($attributes);
            $tenant->save();

            $tenant->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $tenant->status,
                'reason' => 'lifecycle_transition',
                'meta' => [
                    'source' => 'ManageTenantLifecycle',
                ],
            ]);

            return $tenant->fresh(['profile', 'domains', 'statusHistories']);
        });
    }
}
