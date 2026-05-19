<?php

namespace Ticket\PublicCatalog\Contracts;

interface PublicContentCatalog
{
    public function list(array $filters = [], int $page = 1, int $perPage = 12): array;

    public function listAcrossTenants(array $filters = [], int $page = 1, int $perPage = 12): array;

    public function find(string $module, string $slug): ?array;

    public function findAcrossTenants(string $module, string $slug, ?string $tenantSlug = null): ?array;

    public function availableFilters(?string $module = null): array;

    public function availableFiltersAcrossTenants(?string $module = null): array;

    public function summaryStats(): array;

    public function summarySnapshotAcrossTenants(): array;

    public function listingPresentation(?string $module = null, ?string $fallback = 'evenements'): array;

    public function categoryOverviewAcrossTenants(): array;

    public function cityOverviewAcrossTenants(): array;

    public function speakerHighlightsAcrossTenants(?int $limit = null): array;

    public function searchSuggestionsAcrossTenants(string $query, ?string $module = null, int $limit = 6): array;

    public function relatedAcrossTenants(string $module, string $slug, int $limit = 3, ?string $tenantSlug = null): array;
}
