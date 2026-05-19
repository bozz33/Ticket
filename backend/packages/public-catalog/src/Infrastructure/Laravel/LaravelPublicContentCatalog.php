<?php

namespace Ticket\PublicCatalog\Infrastructure\Laravel;

use Ticket\PublicCatalog\Application\PublicContentService;
use Ticket\PublicCatalog\Contracts\PublicContentCatalog;

class LaravelPublicContentCatalog implements PublicContentCatalog
{
    public function __construct(private readonly PublicContentService $content) {}

    public function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        return $this->content->list($filters, $page, $perPage);
    }

    public function listAcrossTenants(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        return $this->content->listAcrossTenants($filters, $page, $perPage);
    }

    public function find(string $module, string $slug): ?array
    {
        return $this->content->find($module, $slug);
    }

    public function findAcrossTenants(string $module, string $slug, ?string $tenantSlug = null): ?array
    {
        return $this->content->findAcrossTenants($module, $slug, $tenantSlug);
    }

    public function availableFilters(?string $module = null): array
    {
        return $this->content->availableFilters($module);
    }

    public function availableFiltersAcrossTenants(?string $module = null): array
    {
        return $this->content->availableFiltersAcrossTenants($module);
    }

    public function summaryStats(): array
    {
        return $this->content->summaryStats();
    }

    public function summarySnapshotAcrossTenants(): array
    {
        return $this->content->summarySnapshotAcrossTenants();
    }

    public function listingPresentation(?string $module = null, ?string $fallback = 'evenements'): array
    {
        return $this->content->listingPresentation($module, $fallback);
    }

    public function categoryOverviewAcrossTenants(): array
    {
        return $this->content->categoryOverviewAcrossTenants();
    }

    public function cityOverviewAcrossTenants(): array
    {
        return $this->content->cityOverviewAcrossTenants();
    }

    public function speakerHighlightsAcrossTenants(?int $limit = null): array
    {
        return $this->content->speakerHighlightsAcrossTenants($limit);
    }

    public function searchSuggestionsAcrossTenants(string $query, ?string $module = null, int $limit = 6): array
    {
        return $this->content->searchSuggestionsAcrossTenants($query, $module, $limit);
    }

    public function relatedAcrossTenants(string $module, string $slug, int $limit = 3, ?string $tenantSlug = null): array
    {
        return $this->content->relatedAcrossTenants($module, $slug, $limit, $tenantSlug);
    }
}
