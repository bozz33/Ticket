<?php

namespace App\Services\Tenancy;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionTenant
{
    public function handle(array $payload): array
    {
        $tenant = DB::connection(config('ticket.central_connection'))->transaction(function () use ($payload): Tenant {
            $slug = Str::slug($payload['slug'] ?? $payload['name']);
            $databaseName = $payload['database_name'] ?? $this->defaultDatabaseName($slug);
            $activate = (bool) ($payload['activate'] ?? false);

            $tenant = Tenant::query()->create([
                'public_id' => (string) Str::uuid(),
                'name' => $payload['name'],
                'slug' => $slug,
                'status' => $activate ? TenantStatus::Active : TenantStatus::Draft,
                'country_code' => $this->uppercaseOrNull($payload['country_code'] ?? null),
                'currency_code' => $this->uppercaseOrNull($payload['currency_code'] ?? null),
                'locale' => $payload['locale'] ?? config('app.locale'),
                'timezone' => $payload['timezone'] ?? config('app.timezone'),
                'database_name' => $databaseName,
                'database_host' => $payload['database_host'] ?? config('ticket.tenant_database_defaults.host'),
                'database_port' => $payload['database_port'] ?? config('ticket.tenant_database_defaults.port'),
                'database_username' => $payload['database_username'] ?? config('ticket.tenant_database_defaults.username'),
                'database_password' => $payload['database_password'] ?? config('ticket.tenant_database_defaults.password'),
                'database_options' => $payload['database_options'] ?? [],
                'activated_at' => $activate ? now() : null,
                'meta' => $payload['meta'] ?? [],
            ]);

            $tenant->profile()->create([
                'public_id' => (string) Str::uuid(),
                'slug' => $slug,
                'display_name' => $payload['display_name'] ?? $payload['name'],
                'description' => $payload['description'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'website_url' => $payload['website_url'] ?? null,
                'is_verified' => false,
                'meta' => Arr::only($payload['meta'] ?? [], ['branding', 'contacts']),
            ]);

            $tenant->statusHistories()->create([
                'from_status' => null,
                'to_status' => $tenant->status,
                'reason' => 'tenant_created',
                'meta' => [
                    'source' => 'ProvisionTenant',
                    'activated' => $activate,
                ],
            ]);

            return $tenant->load(['profile', 'domains', 'statusHistories']);
        });

        app(TenantStorageManager::class)->ensure($tenant);
        app(SyncCentralCategoriesToTenant::class)->handle($tenant);

        $tenantAdmin = $this->provisionTenantAdmin($tenant, $payload);
        $subscription = $this->assignInitialPlan($tenant, $payload);

        return [
            'tenant' => $tenant->fresh(['profile', 'domains', 'statusHistories', 'subscriptions.plan']),
            'tenant_admin' => $tenantAdmin,
            'subscription' => $subscription?->load('plan'),
        ];
    }

    protected function defaultDatabaseName(string $slug): string
    {
        return sprintf('%s%s', config('ticket.tenant_database_defaults.prefix', 'ticket_'), Str::of($slug)->replace('-', '_'));
    }

    protected function uppercaseOrNull(?string $value): ?string
    {
        return $value !== null ? Str::upper($value) : null;
    }

    protected function provisionTenantAdmin(Tenant $tenant, array $payload): array
    {
        $admin = $payload['admin'];
        $plainPassword = $admin['password'];
        $username = Str::lower($admin['username'] ?? Str::before($admin['email'], '@'));

        $user = $tenant->run(function () use ($tenant, $admin, $username): User {
            $user = User::query()->create([
                'name' => $admin['name'] ?? sprintf('%s Admin', $tenant->name),
                'username' => $username,
                'email' => Str::lower($admin['email']),
                'password' => $admin['password'],
                'phone' => $admin['phone'] ?? null,
                'locale' => $admin['locale'] ?? $tenant->locale ?? config('app.locale'),
                'timezone' => $admin['timezone'] ?? $tenant->timezone ?? config('app.timezone'),
                'is_active' => true,
            ]);

            $ownerRole = Role::query()->where('guard_name', 'tenant')->where('name', 'owner')->first();

            if ($ownerRole !== null) {
                $user->assignRole($ownerRole);
            }

            return $user;
        });

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => $plainPassword,
            'public_url' => rtrim(config('ticket.public_frontend_url', config('app.url')), '/') . sprintf('/fr/organisateurs/%s?tab=events', $tenant->public_id),
            'login_url' => url(sprintf('/tenants/%s/admin/login', $tenant->slug)),
        ];
    }

    protected function assignInitialPlan(Tenant $tenant, array $payload)
    {
        $planId = $payload['plan_id'] ?? null;

        if ($planId === null) {
            return null;
        }

        $plan = Plan::query()->findOrFail($planId);
        $now = now();
        $trialEndsAt = $plan->trial_days > 0 ? $now->copy()->addDays($plan->trial_days) : null;

        return $tenant->subscriptions()->create([
            'plan_id' => $plan->getKey(),
            'status' => $trialEndsAt !== null ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
            'started_at' => $now,
            'trial_ends_at' => $trialEndsAt,
            'meta' => [
                'source' => 'ProvisionTenant',
            ],
        ]);
    }
}
