<?php

namespace Ticket\ContentStands\Application;

use App\Models\Stand;
use App\Support\Microservices\CatalogProjectionPayload;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\ContentStands\Contracts\StandContentCatalog;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Tenancy\Application\TenantPublicProfileService;

class StandService implements StandContentCatalog
{
    public function __construct(
        private readonly TenantPublicProfileService $profiles,
        private readonly DomainEventBridge $events,
    ) {}

    public function list(?string $status = null): mixed
    {
        return Stand::query()
            ->with(['organizationProfile', 'category', 'offers'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('public_status_code', $status))
            ->latest()
            ->get();
    }

    public function create(array $payload): Stand
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): Stand {
            $name = (string) ($payload['name'] ?? $payload['title'] ?? '');

            $stand = Stand::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_profile_id' => $payload['organization_profile_id'] ?? $this->profiles->getOrCreate()->id,
                'category_id' => $payload['category_id'] ?? null,
                'public_status_code' => $payload['public_status_code'] ?? 'draft',
                'name' => $name,
                'slug' => $payload['slug'] ?? Str::slug($name),
                'summary' => $payload['summary'] ?? null,
                'description' => $payload['description'] ?? null,
                'currency_code' => $payload['currency_code'] ?? null,
                'price_amount' => max(0, (int) ($payload['price_amount'] ?? 0)),
                'quantity_available' => max(0, (int) ($payload['quantity_available'] ?? 0)),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'published_at' => $payload['published_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);

            $stand = $stand->fresh(['organizationProfile', 'category', 'offers']);

            if ($stand->public_status_code === 'published') {
                $this->events->publish(
                    DomainEventNames::STAND_RESERVED,
                    CatalogProjectionPayload::fromContent($stand, 'stands', ['title' => $stand->name]),
                    'stands',
                    (string) $stand->getKey(),
                    ['module' => 'content-stands', 'tenant_id' => tenant('id')],
                );
            }

            return $stand;
        });
    }

    public function findByIdentifier(string $identifier): ?Stand
    {
        return Stand::query()
            ->with(['organizationProfile', 'category', 'offers'])
            ->when(Str::isUuid($identifier), fn ($query) => $query->where('public_id', $identifier), fn ($query) => $query->where('slug', $identifier))
            ->first();
    }
}
