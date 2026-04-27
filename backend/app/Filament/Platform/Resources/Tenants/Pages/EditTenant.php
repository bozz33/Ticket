<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Enums\SubscriptionStatus;
use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected string|null $maxWidth = '7xl';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tenant $record */
        $record = $this->getRecord()->load(['profile', 'domains', 'subscriptions']);
        $admin = $record->run(fn () => User::query()->orderBy('id')->first());
        $activeSubscription = $record->subscriptions->first(fn ($subscription) => in_array($subscription->status?->value ?? $subscription->status, [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value], true));

        $data['display_name'] = $record->profile?->display_name;
        $data['description'] = $record->profile?->description;
        $data['email'] = $record->profile?->email;
        $data['phone'] = $record->profile?->phone;
        $data['website_url'] = $record->profile?->website_url;
        $data['plan_id'] = $activeSubscription?->plan_id;
        $data['admin'] = [
            'name' => $admin?->name,
            'username' => $admin?->username,
            'email' => $admin?->email,
            'phone' => $admin?->phone,
            'locale' => $admin?->locale,
            'timezone' => $admin?->timezone,
        ];

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        /** @var Tenant $record */
        $adminData = $data['admin'] ?? [];
        $adminUsername = filled($adminData['username'] ?? null)
            ? Str::lower($adminData['username'])
            : Str::lower(Str::before((string) ($adminData['email'] ?? 'admin@local'), '@'));

        $record->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?: $data['name']),
            'country_code' => $data['country_code'] ?: null,
            'currency_code' => $data['currency_code'] ?: null,
            'locale' => $data['locale'] ?: null,
            'timezone' => $data['timezone'] ?: null,
            'meta' => $data['meta'] ?? [],
        ]);

        $record->profile()->updateOrCreate([], [
            'public_id' => $record->profile?->public_id ?? (string) Str::uuid(),
            'slug' => $record->slug,
            'display_name' => $data['display_name'] ?: $data['name'],
            'description' => $data['description'] ?: null,
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'website_url' => $data['website_url'] ?: null,
            'meta' => [],
        ]);

        $record->run(function () use ($record, $adminData, $adminUsername): void {
            $admin = User::query()->orderBy('id')->first();

            if ($admin === null) {
                User::query()->create([
                    'name' => $adminData['name'] ?? sprintf('%s Admin', $record->name),
                    'username' => $adminUsername,
                    'email' => Str::lower($adminData['email']),
                    'password' => $adminData['password'],
                    'phone' => $adminData['phone'] ?? null,
                    'locale' => $adminData['locale'] ?? $record->locale,
                    'timezone' => $adminData['timezone'] ?? $record->timezone,
                    'is_active' => true,
                ]);

                return;
            }

            $update = [
                'name' => $adminData['name'] ?? $admin->name,
                'username' => $adminUsername ?: $admin->username,
                'email' => isset($adminData['email']) ? Str::lower($adminData['email']) : $admin->email,
                'phone' => $adminData['phone'] ?? $admin->phone,
                'locale' => $adminData['locale'] ?? $admin->locale,
                'timezone' => $adminData['timezone'] ?? $admin->timezone,
            ];

            if (filled($adminData['password'] ?? null)) {
                $update['password'] = $adminData['password'];
            }

            $admin->update($update);
        });

        $planId = $data['plan_id'] ?? null;
        if ($planId !== null) {
            $current = $record->subscriptions()->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])->latest('id')->first();

            if ($current === null || (int) $current->plan_id !== (int) $planId) {
                $record->subscriptions()->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])->update([
                    'status' => SubscriptionStatus::Replaced->value,
                    'cancelled_at' => now(),
                ]);

                $plan = Plan::query()->findOrFail($planId);
                $trialEndsAt = $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null;

                $record->subscriptions()->create([
                    'plan_id' => $plan->getKey(),
                    'status' => $trialEndsAt !== null ? SubscriptionStatus::Trialing->value : SubscriptionStatus::Active->value,
                    'started_at' => now(),
                    'trial_ends_at' => $trialEndsAt,
                    'meta' => [
                        'source' => 'FilamentEditTenant',
                    ],
                ]);
            }
        }

        return $record->fresh(['profile', 'domains', 'subscriptions']);
    }
}
