<?php

namespace Ticket\ContentEvents\Application;

use App\Models\Event;
use App\Support\Microservices\CatalogProjectionPayload;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\ContentEvents\Contracts\EventContentCatalog;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Tenancy\Application\TenantPublicProfileService;

class EventService implements EventContentCatalog
{
    public function __construct(
        protected TenantPublicProfileService $tenantPublicProfileService,
        protected DomainEventBridge $events,
    ) {}

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

            $event = $event->fresh(['organizationProfile', 'category', 'dates']);

            if ($event->public_status_code === 'published') {
                $this->events->publish(
                    DomainEventNames::EVENT_PUBLISHED,
                    $this->catalogPayload($event),
                    'events',
                    (string) $event->getKey(),
                    ['module' => 'content-events', 'tenant_id' => tenant('id')],
                );
            }

            return $event;
        });
    }

    public function findByIdentifier(string $identifier): ?Event
    {
        if (Str::isUuid($identifier)) {
            return Event::query()
                ->with(['organizationProfile', 'category', 'dates'])
                ->where('public_id', $identifier)
                ->first();
        }

        return Event::query()
            ->with(['organizationProfile', 'category', 'dates'])
            ->where('slug', $identifier)
            ->first();
    }

    private function catalogPayload(Event $event): array
    {
        return CatalogProjectionPayload::fromContent($event, 'events', [
            'starts_at' => CatalogProjectionPayload::isoDate($event->dates->min('starts_at')),
            'ends_at' => CatalogProjectionPayload::isoDate($event->dates->max('ends_at')),
        ]);
    }
}
