<?php

namespace Ticket\ContentCallsForProjects\Application;

use App\Models\CallForProject;
use App\Support\Microservices\CatalogProjectionPayload;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\ContentCallsForProjects\Contracts\CallForProjectContentCatalog;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Tenancy\Application\TenantPublicProfileService;

class CallForProjectService implements CallForProjectContentCatalog
{
    public function __construct(
        private readonly TenantPublicProfileService $profiles,
        private readonly DomainEventBridge $events,
    ) {}

    public function list(?string $status = null): mixed
    {
        return CallForProject::query()
            ->with(['organizationProfile', 'category', 'offers', 'formDefinition'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('public_status_code', $status))
            ->latest()
            ->get();
    }

    public function create(array $payload): CallForProject
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): CallForProject {
            $call = CallForProject::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_profile_id' => $payload['organization_profile_id'] ?? $this->profiles->getOrCreate()->id,
                'category_id' => $payload['category_id'] ?? null,
                'public_status_code' => $payload['public_status_code'] ?? 'draft',
                'title' => $payload['title'],
                'slug' => $payload['slug'] ?? Str::slug($payload['title']),
                'summary' => $payload['summary'] ?? null,
                'description' => $payload['description'] ?? null,
                'application_opens_at' => $payload['application_opens_at'] ?? null,
                'application_closes_at' => $payload['application_closes_at'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'published_at' => $payload['published_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);

            $call = $call->fresh(['organizationProfile', 'category', 'offers', 'formDefinition']);

            if ($call->public_status_code === 'published') {
                $this->events->publish(
                    DomainEventNames::CALL_FOR_PROJECT_OPENED,
                    CatalogProjectionPayload::fromContent($call, 'calls-for-projects', [
                        'starts_at' => CatalogProjectionPayload::isoDate($call->application_opens_at),
                        'ends_at' => CatalogProjectionPayload::isoDate($call->application_closes_at),
                    ]),
                    'calls_for_projects',
                    (string) $call->getKey(),
                    ['module' => 'content-calls-for-projects', 'tenant_id' => tenant('id')],
                );
            }

            return $call;
        });
    }

    public function findByIdentifier(string $identifier): ?CallForProject
    {
        return CallForProject::query()
            ->with(['organizationProfile', 'category', 'offers', 'formDefinition'])
            ->when(Str::isUuid($identifier), fn ($query) => $query->where('public_id', $identifier), fn ($query) => $query->where('slug', $identifier))
            ->first();
    }
}
