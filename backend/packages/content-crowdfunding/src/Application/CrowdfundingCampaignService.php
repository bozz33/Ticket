<?php

namespace Ticket\ContentCrowdfunding\Application;

use App\Models\CrowdfundingCampaign;
use App\Support\Microservices\CatalogProjectionPayload;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\ContentCrowdfunding\Contracts\CrowdfundingContentCatalog;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Tenancy\Application\TenantPublicProfileService;

class CrowdfundingCampaignService implements CrowdfundingContentCatalog
{
    public function __construct(
        private readonly TenantPublicProfileService $profiles,
        private readonly DomainEventBridge $events,
    ) {}

    public function list(?string $status = null): mixed
    {
        return CrowdfundingCampaign::query()
            ->with(['organizationProfile', 'category', 'offers', 'contributions'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('public_status_code', $status))
            ->latest()
            ->get();
    }

    public function create(array $payload): CrowdfundingCampaign
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): CrowdfundingCampaign {
            $campaign = CrowdfundingCampaign::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_profile_id' => $payload['organization_profile_id'] ?? $this->profiles->getOrCreate()->id,
                'category_id' => $payload['category_id'] ?? null,
                'public_status_code' => $payload['public_status_code'] ?? 'draft',
                'title' => $payload['title'],
                'slug' => $payload['slug'] ?? Str::slug($payload['title']),
                'summary' => $payload['summary'] ?? null,
                'description' => $payload['description'] ?? null,
                'currency_code' => $payload['currency_code'] ?? null,
                'target_amount' => max(0, (int) ($payload['target_amount'] ?? 0)),
                'raised_amount' => max(0, (int) ($payload['raised_amount'] ?? 0)),
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'published_at' => $payload['published_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);

            $campaign = $campaign->fresh(['organizationProfile', 'category', 'offers', 'contributions']);

            if ($campaign->public_status_code === 'published') {
                $this->events->publish(
                    DomainEventNames::CROWDFUNDING_CAMPAIGN_LAUNCHED,
                    CatalogProjectionPayload::fromContent($campaign, 'crowdfunding', ['price_from' => 0, 'is_free' => true]),
                    'crowdfunding_campaigns',
                    (string) $campaign->getKey(),
                    ['module' => 'content-crowdfunding', 'tenant_id' => tenant('id')],
                );
            }

            return $campaign;
        });
    }

    public function findByIdentifier(string $identifier): ?CrowdfundingCampaign
    {
        return CrowdfundingCampaign::query()
            ->with(['organizationProfile', 'category', 'offers', 'contributions'])
            ->when(Str::isUuid($identifier), fn ($query) => $query->where('public_id', $identifier), fn ($query) => $query->where('slug', $identifier))
            ->first();
    }
}
