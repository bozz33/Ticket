<?php

namespace Ticket\ContentTraining\Application;

use App\Models\Training;
use App\Support\Microservices\CatalogProjectionPayload;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\ContentTraining\Contracts\TrainingContentCatalog;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Tenancy\Application\TenantPublicProfileService;

class TrainingService implements TrainingContentCatalog
{
    public function __construct(
        private readonly TenantPublicProfileService $profiles,
        private readonly DomainEventBridge $events,
    ) {}

    public function list(?string $status = null): mixed
    {
        return Training::query()
            ->with(['organizationProfile', 'category', 'offers'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('public_status_code', $status))
            ->latest()
            ->get();
    }

    public function create(array $payload): Training
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): Training {
            $training = Training::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_profile_id' => $payload['organization_profile_id'] ?? $this->profiles->getOrCreate()->id,
                'category_id' => $payload['category_id'] ?? null,
                'public_status_code' => $payload['public_status_code'] ?? 'draft',
                'title' => $payload['title'],
                'slug' => $payload['slug'] ?? Str::slug($payload['title']),
                'summary' => $payload['summary'] ?? null,
                'description' => $payload['description'] ?? null,
                'timezone' => $payload['timezone'] ?? config('app.timezone'),
                'currency_code' => $payload['currency_code'] ?? null,
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'venue_name' => $payload['venue_name'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'published_at' => $payload['published_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);

            $training = $training->fresh(['organizationProfile', 'category', 'offers']);

            if ($training->public_status_code === 'published') {
                $this->events->publish(
                    DomainEventNames::TRAINING_SESSION_SCHEDULED,
                    CatalogProjectionPayload::fromContent($training, 'training'),
                    'training',
                    (string) $training->getKey(),
                    ['module' => 'content-training', 'tenant_id' => tenant('id')],
                );
            }

            return $training;
        });
    }

    public function findByIdentifier(string $identifier): ?Training
    {
        return Training::query()
            ->with(['organizationProfile', 'category', 'offers'])
            ->when(Str::isUuid($identifier), fn ($query) => $query->where('public_id', $identifier), fn ($query) => $query->where('slug', $identifier))
            ->first();
    }
}
