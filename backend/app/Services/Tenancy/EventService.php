<?php

namespace App\Services\Tenancy;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    public function __construct(protected TenantPublicProfileService $tenantPublicProfileService)
    {
    }

    public function list(?string $status = null): mixed
    {
        return Event::query()
            ->with(['organizationProfile', 'category', 'dates'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('public_status_code', $status))
            ->latest()
            ->get();
    }

    public function create(array $payload): Event
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): Event {
            $organizationProfileId = $payload['organization_profile_id'] ?? $this->tenantPublicProfileService->getOrCreate()->id;

            $event = Event::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_profile_id' => $organizationProfileId,
                'category_id' => $payload['category_id'] ?? null,
                'public_status_code' => $payload['public_status_code'] ?? 'draft',
                'title' => $payload['title'],
                'slug' => $payload['slug'] ?? Str::slug($payload['title']),
                'summary' => $payload['summary'] ?? null,
                'description' => $payload['description'] ?? null,
                'timezone' => $payload['timezone'] ?? config('app.timezone'),
                'currency_code' => $payload['currency_code'] ?? null,
                'country_code' => $payload['country_code'] ?? null,
                'city_id' => $payload['city_id'] ?? null,
                'venue_name' => $payload['venue_name'] ?? null,
                'venue_address' => $payload['venue_address'] ?? null,
                'cover_image_url' => $payload['cover_image_url'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'published_at' => $payload['published_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);

            foreach ($payload['dates'] ?? [] as $date) {
                $event->dates()->create([
                    'starts_at' => $date['starts_at'],
                    'ends_at' => $date['ends_at'] ?? null,
                    'timezone' => $date['timezone'] ?? $event->timezone,
                    'is_all_day' => (bool) ($date['is_all_day'] ?? false),
                    'sort_order' => $date['sort_order'] ?? 0,
                    'meta' => $date['meta'] ?? [],
                ]);
            }

            return $event->fresh(['organizationProfile', 'category', 'dates']);
        });
    }

    public function findByIdentifier(string $identifier): ?Event
    {
        return Event::query()
            ->with(['organizationProfile', 'category', 'dates'])
            ->where('slug', $identifier)
            ->orWhere('public_id', $identifier)
            ->first();
    }
}
