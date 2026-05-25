<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\GlobalCache;
use Ticket\PublicCatalog\Contracts\PublicContentCatalog;

class PublicContentController extends Controller
{
    private const CONTENT_TTL = 120;

    private const FILTERS_TTL = 300;

    private const OVERVIEW_TTL = 300;

    private const SEARCH_TTL = 60;

    public function __construct(
        private readonly PublicContentCatalog $service,
    ) {}

    public function globalIndex(Request $request): JsonResponse
    {
        $filters = $request->only(['module', 'q', 'category', 'city', 'date_from', 'date_to', 'price', 'sort', 'featured', 'include_past']);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(48, max(1, (int) $request->query('per_page', 12)));

        $payload = $this->remember(
            'global_index',
            ['filters' => $filters, 'page' => $page, 'per_page' => $perPage],
            self::CONTENT_TTL,
            function () use ($filters, $page, $perPage): array {
                $result = $this->service->listAcrossTenants($filters, $page, $perPage);
                $available = $this->service->availableFiltersAcrossTenants($filters['module'] ?? null);
                $presentation = $this->service->listingPresentation(
                    is_string($filters['module'] ?? null) ? $filters['module'] : null,
                    'evenements',
                );

                return [
                    'data' => $result['items'],
                    'meta' => [
                        'current_page' => $result['currentPage'],
                        'total' => $result['total'],
                        'total_pages' => $result['totalPages'],
                        'per_page' => $perPage,
                    ],
                    'filters' => $available,
                    'presentation' => $presentation,
                ];
            },
        );

        return $this->cachedJson($payload, self::CONTENT_TTL);
    }

    public function index(Request $request, string $tenant): JsonResponse
    {
        $filters = $request->only(['module', 'q', 'category', 'city', 'date_from', 'date_to', 'price', 'sort', 'featured', 'include_past']);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(48, max(1, (int) $request->query('per_page', 12)));

        $payload = $this->remember(
            'tenant_index',
            ['tenant' => $tenant, 'filters' => $filters, 'page' => $page, 'per_page' => $perPage],
            self::CONTENT_TTL,
            function () use ($filters, $page, $perPage): array {
                $result = $this->service->list($filters, $page, $perPage);
                $available = $this->service->availableFilters($filters['module'] ?? null);
                $presentation = $this->service->listingPresentation(
                    is_string($filters['module'] ?? null) ? $filters['module'] : null,
                    'evenements',
                );

                return [
                    'data' => $result['items'],
                    'meta' => [
                        'current_page' => $result['currentPage'],
                        'total' => $result['total'],
                        'total_pages' => $result['totalPages'],
                        'per_page' => $perPage,
                    ],
                    'filters' => $available,
                    'presentation' => $presentation,
                ];
            },
        );

        return $this->cachedJson($payload, self::CONTENT_TTL);
    }

    public function show(string $tenant, string $module, string $slug): JsonResponse
    {
        $item = $this->remember(
            'tenant_show',
            ['tenant' => $tenant, 'module' => $module, 'slug' => $slug],
            self::CONTENT_TTL,
            fn (): ?array => $this->service->find($module, $slug),
        );

        if ($item === null) {
            return response()->json(['message' => 'Contenu introuvable.'], 404);
        }

        return $this->cachedJson(['data' => $item], self::CONTENT_TTL);
    }

    public function globalShow(Request $request, string $module, string $slug): JsonResponse
    {
        $tenant = $request->query('tenant');
        $resolvedTenant = is_string($tenant) && trim($tenant) !== '' ? trim($tenant) : null;
        $item = $this->remember(
            'global_show',
            ['module' => $module, 'slug' => $slug, 'tenant' => $resolvedTenant],
            self::CONTENT_TTL,
            fn (): ?array => $this->service->findAcrossTenants(
                $module,
                $slug,
                $resolvedTenant,
            ),
        );

        if ($item === null) {
            return response()->json(['message' => 'Contenu introuvable.'], 404);
        }

        return $this->cachedJson(['data' => $item], self::CONTENT_TTL);
    }

    public function globalCategoryOverview(): JsonResponse
    {
        $payload = $this->remember(
            'category_overview',
            [],
            self::OVERVIEW_TTL,
            fn (): array => [
                'data' => $this->service->categoryOverviewAcrossTenants(),
            ],
        );

        return $this->cachedJson($payload, self::OVERVIEW_TTL);
    }

    public function globalCityOverview(): JsonResponse
    {
        $payload = $this->remember(
            'city_overview',
            [],
            self::OVERVIEW_TTL,
            fn (): array => [
                'data' => $this->service->cityOverviewAcrossTenants(),
            ],
        );

        return $this->cachedJson($payload, self::OVERVIEW_TTL);
    }

    public function globalSpeakerHighlights(Request $request): JsonResponse
    {
        $limit = max(0, (int) $request->query('limit', 0));

        $payload = $this->remember(
            'speaker_overview',
            ['limit' => $limit],
            self::OVERVIEW_TTL,
            fn (): array => [
                'data' => $this->service->speakerHighlightsAcrossTenants($limit > 0 ? $limit : null),
            ],
        );

        return $this->cachedJson($payload, self::OVERVIEW_TTL);
    }

    public function globalSummary(): JsonResponse
    {
        $payload = $this->remember(
            'global_summary',
            [],
            self::OVERVIEW_TTL,
            fn (): array => [
                'data' => $this->service->summarySnapshotAcrossTenants(),
            ],
        );

        return $this->cachedJson($payload, self::OVERVIEW_TTL);
    }

    public function globalSearchSuggestions(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $module = $request->query('module');
        $limit = min(12, max(1, (int) $request->query('limit', 6)));
        $resolvedModule = is_string($module) && trim($module) !== '' ? trim($module) : null;
        $payload = $this->remember(
            'search_suggestions',
            ['q' => $query, 'module' => $resolvedModule, 'limit' => $limit],
            self::SEARCH_TTL,
            fn (): array => [
                'data' => $this->service->searchSuggestionsAcrossTenants(
                    $query,
                    $resolvedModule,
                    $limit,
                ),
            ],
        );

        return $this->cachedJson($payload, self::SEARCH_TTL);
    }

    public function globalRelated(Request $request, string $module, string $slug): JsonResponse
    {
        $tenant = $request->query('tenant');
        $limit = min(12, max(1, (int) $request->query('limit', 3)));
        $resolvedTenant = is_string($tenant) && trim($tenant) !== '' ? trim($tenant) : null;
        $payload = $this->remember(
            'global_related',
            ['module' => $module, 'slug' => $slug, 'tenant' => $resolvedTenant, 'limit' => $limit],
            self::CONTENT_TTL,
            fn (): array => [
                'data' => $this->service->relatedAcrossTenants(
                    $module,
                    $slug,
                    $limit,
                    $resolvedTenant,
                ),
            ],
        );

        return $this->cachedJson($payload, self::CONTENT_TTL);
    }

    public function filters(Request $request, string $tenant): JsonResponse
    {
        $module = $request->query('module');
        $payload = $this->remember(
            'tenant_filters',
            ['tenant' => $tenant, 'module' => $module],
            self::FILTERS_TTL,
            fn (): array => ['data' => $this->service->availableFilters($module)],
        );

        return $this->cachedJson($payload, self::FILTERS_TTL);
    }

    public function globalFilters(Request $request): JsonResponse
    {
        $module = $request->query('module');
        $payload = $this->remember(
            'global_filters',
            ['module' => $module],
            self::FILTERS_TTL,
            fn (): array => ['data' => $this->service->availableFiltersAcrossTenants($module)],
        );

        return $this->cachedJson($payload, self::FILTERS_TTL);
    }

    private function remember(string $prefix, array $context, int $ttl, callable $callback): mixed
    {
        return GlobalCache::remember(
            $this->cacheKey($prefix, $context),
            now()->addSeconds($ttl),
            $callback,
        );
    }

    private function cacheKey(string $prefix, array $context): string
    {
        return sprintf('public_content:%s:%s', $prefix, md5(json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    private function cachedJson(array $payload, int $ttl): JsonResponse
    {
        return response()->json($payload, 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', $ttl, $ttl),
        ]);
    }
}
