<?php

namespace Ticket\PublicCatalog\Application;

use App\Enums\TenantStatus;
use App\Models\CallForProject;
use App\Models\Category;
use App\Models\City;
use App\Models\CrowdfundingCampaign;
use App\Models\Offer;
use App\Models\Stand;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Ticket\PublicCatalog\Domain\PublicCatalogModules;

class PublicContentService
{
    private array $cityCache = [];

    public function __construct(
        private readonly CallForProjectApplicationFormService $callForProjectApplicationFormService,
        private readonly PublicCatalogModules $modules,
        private readonly ?PublicCatalogProjectionReader $projectionReader = null,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────────

    public function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $items = $this->applySort($this->collectItems($filters), $filters['sort'] ?? 'recent');

        $total = $items->count();
        $paginated = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'items' => $paginated->all(),
            'total' => $total,
            'totalPages' => (int) ceil($total / max(1, $perPage)),
            'currentPage' => $page,
        ];
    }

    public function listAcrossTenants(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $projection = $this->projectionReader?->list($filters, $page, $perPage);

        if ($projection !== null) {
            return $projection;
        }

        $items = $this->applySort($this->collectItemsAcrossTenants($filters), $filters['sort'] ?? 'recent');

        $total = $items->count();
        $paginated = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'items' => $paginated->all(),
            'total' => $total,
            'totalPages' => (int) ceil($total / max(1, $perPage)),
            'currentPage' => $page,
        ];
    }

    public function find(string $module, string $slug): ?array
    {
        $modelClass = $this->modules->modelClass($module);
        if (! $modelClass) {
            return null;
        }

        $recordQuery = $modelClass::query()
            ->where('slug', $slug)
            ->where('is_active', true);

        if ($module === 'evenements') {
            $recordQuery->withCount('likes');
            $this->excludeEndedEvents($recordQuery);
        }

        $record = $recordQuery
            ->with($this->eagerLoads($module))
            ->first();

        return $record ? $this->transform($record, $module) : null;
    }

    public function findAcrossTenants(string $module, string $slug, ?string $tenantSlug = null): ?array
    {
        $projected = $this->projectionReader?->find($module, $slug, $tenantSlug);

        if ($projected !== null) {
            return $projected;
        }

        $tenants = $tenantSlug
            ? $this->activeTenants()->filter(
                fn (Tenant $tenant): bool => $tenant->slug === $tenantSlug || $tenant->public_id === $tenantSlug,
            )->values()
            : $this->activeTenants();

        foreach ($tenants as $tenant) {
            $record = $tenant->run(fn (): ?array => $this->find($module, $slug));

            if ($record !== null) {
                return $record;
            }
        }

        return null;
    }

    public function availableFilters(?string $module = null): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->when(
                $this->modules->scope($module) !== null,
                fn (Builder $query) => $query->whereIn('module_scope', ['global', $this->modules->scope($module)]),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();
        $cityIds = collect();

        $modules = ($module && $this->modules->has($module))
            ? [$module]
            : $this->modules->keys();

        foreach ($modules as $moduleName) {
            $modelClass = $this->modules->modelClass($moduleName);
            $recordsQuery = $modelClass::query()
                ->where('is_active', true);

            if ($moduleName === 'evenements') {
                $this->excludeEndedEvents($recordsQuery);
            }

            $records = $recordsQuery->get(['id', 'category_id', isset((new $modelClass)->city_id) ? 'city_id' : 'id']);

            foreach ($records as $record) {
                if (isset($record->city_id) && $record->city_id) {
                    $cityIds->push($record->city_id);
                }
            }
        }

        // Bulk city name lookup
        $cityNames = City::whereIn('id', $cityIds->unique()->all())
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        return [
            'categories' => $categories->all(),
            'cities' => $cityNames->all(),
        ];
    }

    public function availableFiltersAcrossTenants(?string $module = null): array
    {
        $projected = $this->projectionReader?->availableFilters($module);

        if ($projected !== null) {
            return $projected;
        }

        $categories = collect();
        $cities = collect();

        foreach ($this->activeTenants() as $tenant) {
            $filters = $tenant->run(fn (): array => $this->availableFilters($module));

            $categories = $categories->merge($filters['categories'] ?? []);
            $cities = $cities->merge($filters['cities'] ?? []);
        }

        return [
            'categories' => $categories->filter()->unique()->sort()->values()->all(),
            'cities' => $cities->filter()->unique()->sort()->values()->all(),
        ];
    }

    public function summaryStats(): array
    {
        $countsByModule = [];
        $total = 0;
        $free = 0;
        $paid = 0;

        foreach ($this->modules->modelMap() as $module => $modelClass) {
            $baseQuery = $modelClass::query()
                ->where('is_active', true)
                ->whereNotNull('published_at');

            if ($module === 'evenements') {
                $this->excludeEndedEvents($baseQuery);
            }

            $moduleCount = (clone $baseQuery)->count();
            $paidCount = $module === 'stands'
                ? (clone $baseQuery)->where('price_amount', '>', 0)->count()
                : (clone $baseQuery)->whereHas('offers', function (Builder $query): void {
                    $query->where('is_active', true)
                        ->where('price_amount', '>', 0);
                })->count();
            $freeCount = max(0, $moduleCount - $paidCount);

            $countsByModule[$module] = $moduleCount;
            $total += $moduleCount;
            $paid += $paidCount;
            $free += $freeCount;
        }

        return [
            'total' => $total,
            'free' => $free,
            'paid' => $paid,
            'byModule' => $countsByModule,
        ];
    }

    public function summarySnapshotAcrossTenants(): array
    {
        $projected = $this->projectionReader?->summarySnapshot();

        if ($projected !== null) {
            return $projected;
        }

        $items = $this->applySort($this->collectItemsAcrossTenants(), 'recent');
        $totalItems = $items->count();
        $freeItems = $items->filter(fn (array $item): bool => (bool) ($item['isFree'] ?? false))->count();
        $moduleCards = collect($this->modules->keys())
            ->map(function (string $module) use ($items): array {
                $presentation = $this->modules->presentation($module);
                $moduleItems = $items->filter(fn (array $item): bool => ($item['module'] ?? null) === $module)->values();
                $sampleImage = (string) ($moduleItems->first()['coverImageUrl'] ?? '');

                return [
                    'module' => $module,
                    'count' => $moduleItems->count(),
                    'image' => $sampleImage !== '' ? $sampleImage : $presentation['heroImageUrl'],
                    'title' => $presentation['title'],
                    'description' => $presentation['description'],
                    'href' => $presentation['href'],
                ];
            })
            ->values()
            ->all();
        $galleryImages = $items
            ->flatMap(function (array $item): array {
                $images = [(string) ($item['coverImageUrl'] ?? '')];

                foreach ((array) ($item['gallery'] ?? []) as $image) {
                    if (is_string($image)) {
                        $images[] = $image;
                    }
                }

                return $images;
            })
            ->filter(fn (string $image): bool => trim($image) !== '')
            ->unique()
            ->take(4)
            ->values()
            ->all();

        return [
            'totalItems' => $totalItems,
            'activeCategories' => $items->pluck('category')->filter()->unique()->count(),
            'activeCities' => $items->pluck('city')->filter()->unique()->count(),
            'activeCountries' => $items->pluck('country')->filter()->unique()->count(),
            'offerCount' => $items->sum(fn (array $item): int => count((array) ($item['tiers'] ?? []))),
            'freeItems' => $freeItems,
            'paidItems' => max(0, $totalItems - $freeItems),
            'galleryImages' => $galleryImages,
            'moduleCards' => $moduleCards,
        ];
    }

    public function listingPresentation(?string $module = null, ?string $fallback = 'evenements'): array
    {
        return $this->modules->listingPresentation($module, $fallback);
    }

    public function categoryOverviewAcrossTenants(): array
    {
        $projected = $this->projectionReader?->groupedOverview('category');

        if ($projected !== null) {
            return $projected;
        }

        return $this->applySort($this->collectItemsAcrossTenants(), 'recent')
            ->filter(fn (array $item): bool => filled($item['category'] ?? null))
            ->groupBy(fn (array $item): string => (string) $item['category'])
            ->sortKeys()
            ->map(fn (Collection $items, string $category): array => [
                'category' => $category,
                'count' => $items->count(),
                'sample' => $items->first() ?: null,
            ])
            ->values()
            ->all();
    }

    public function cityOverviewAcrossTenants(): array
    {
        $projected = $this->projectionReader?->groupedOverview('city');

        if ($projected !== null) {
            return $projected;
        }

        return $this->applySort($this->collectItemsAcrossTenants(), 'recent')
            ->filter(fn (array $item): bool => filled($item['city'] ?? null))
            ->groupBy(fn (array $item): string => (string) $item['city'])
            ->sortKeys()
            ->map(fn (Collection $items, string $city): array => [
                'city' => $city,
                'count' => $items->count(),
                'sample' => $items->first() ?: null,
            ])
            ->values()
            ->all();
    }

    public function speakerHighlightsAcrossTenants(?int $limit = null): array
    {
        $projected = $this->projectionReader?->speakerHighlights($limit);

        if ($projected !== null) {
            return $projected;
        }

        $items = $this->applySort($this->collectItemsAcrossTenants(), 'popular');

        $speakers = $items
            ->flatMap(function (array $item): array {
                return collect($item['speakers'] ?? [])
                    ->filter(fn ($speaker): bool => is_array($speaker) && filled($speaker['name'] ?? null))
                    ->map(fn (array $speaker): array => [
                        'name' => (string) ($speaker['name'] ?? ''),
                        'role' => (string) ($speaker['role'] ?? ''),
                        'imageUrl' => (string) ($speaker['imageUrl'] ?? ''),
                        'itemTitle' => (string) ($item['title'] ?? ''),
                        'itemSlug' => (string) ($item['slug'] ?? ''),
                        'itemModule' => (string) ($item['module'] ?? ''),
                        'category' => (string) ($item['category'] ?? ''),
                        'city' => (string) ($item['city'] ?? ''),
                        'organizerSlug' => (string) ($item['organizerSlug'] ?? ''),
                        'organizerName' => (string) data_get($item, 'organizers.0.name', 'Organisateur'),
                        'organizerLogoUrl' => (string) data_get($item, 'organizers.0.imageUrl', $item['coverImageUrl'] ?? ''),
                    ])
                    ->values()
                    ->all();
            })
            ->values();

        if ($limit !== null && $limit > 0) {
            return $speakers->take($limit)->values()->all();
        }

        return $speakers->all();
    }

    public function searchSuggestionsAcrossTenants(string $query, ?string $module = null, int $limit = 6): array
    {
        $projected = $this->projectionReader?->searchSuggestions($query, $module, $limit);

        if ($projected !== null) {
            return $projected;
        }

        $normalizedQuery = trim($query);

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < 2) {
            return [];
        }

        $modules = ($module && $this->modules->has($module))
            ? [$module]
            : $this->modules->keys();
        $suggestions = collect();

        foreach ($this->activeTenants() as $tenant) {
            $tenantSuggestions = $tenant->run(
                fn (): Collection => $this->searchSuggestions($normalizedQuery, $modules, $limit),
            );

            $suggestions = $suggestions->merge($tenantSuggestions);
        }

        return $suggestions
            ->sort(function (array $left, array $right): int {
                if (($left['_score'] ?? 0) !== ($right['_score'] ?? 0)) {
                    return ($right['_score'] ?? 0) <=> ($left['_score'] ?? 0);
                }

                return strcmp((string) ($right['_publishedAt'] ?? ''), (string) ($left['_publishedAt'] ?? ''));
            })
            ->unique(fn (array $suggestion): string => (string) ($suggestion['href'] ?? ''))
            ->take(max(1, $limit))
            ->values()
            ->map(fn (array $suggestion): array => [
                'title' => $suggestion['title'],
                'href' => $suggestion['href'],
                'module' => $suggestion['module'],
                'city' => $suggestion['city'],
                'category' => $suggestion['category'],
            ])
            ->all();
    }

    public function relatedAcrossTenants(string $module, string $slug, int $limit = 3, ?string $tenantSlug = null): array
    {
        $projected = $this->projectionReader?->related($module, $slug, $limit, $tenantSlug);

        if ($projected !== null) {
            return $projected;
        }

        $target = $this->findAcrossTenants($module, $slug, $tenantSlug);

        if ($target === null) {
            return [];
        }

        $resolvedTenantSlug = $tenantSlug ?: ($target['organizerSlug'] ?? null);
        $tenants = $resolvedTenantSlug
            ? $this->activeTenants()->filter(
                fn (Tenant $tenant): bool => $tenant->slug === $resolvedTenantSlug || $tenant->public_id === $resolvedTenantSlug,
            )->values()
            : $this->activeTenants();
        $related = collect();

        foreach ($tenants as $tenant) {
            $tenantRelated = $tenant->run(
                fn (): Collection => $this->relatedItems($module, $slug, (string) ($target['category'] ?? ''), $limit),
            );

            $related = $related->merge($tenantRelated);
        }

        return $related
            ->unique(fn (array $item): string => (string) ($item['id'] ?? ''))
            ->take(max(1, $limit))
            ->values()
            ->all();
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    private function activeTenants(): Collection
    {
        return Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    private function collectItemsAcrossTenants(array $filters = []): Collection
    {
        $items = collect();

        foreach ($this->activeTenants() as $tenant) {
            $tenantItems = $tenant->run(fn (): Collection => $this->collectItems($filters));

            $items = $items->merge($tenantItems);
        }

        return $items->values();
    }

    private function searchSuggestions(string $query, array $modules, int $limit): Collection
    {
        $suggestions = collect();

        foreach ($modules as $module) {
            $modelClass = $this->modules->modelClass($module);
            $titleField = $module === 'stands' ? 'name' : 'title';
            $columns = ['public_id', 'slug', 'category_id', 'published_at', $titleField];

            if ($module === 'evenements') {
                $columns[] = 'city_id';
            }

            $recordsQuery = $modelClass::query()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where(function (Builder $queryBuilder) use ($titleField, $query): void {
                    $queryBuilder->where($titleField, 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%");
                });

            if ($module === 'evenements') {
                $this->excludeEndedEvents($recordsQuery);
            }

            $records = $recordsQuery
                ->with(['category'])
                ->orderByDesc('published_at')
                ->limit(max(1, $limit))
                ->get($columns);

            foreach ($records as $record) {
                $suggestions->push($this->transformSuggestion($record, $module, $query));
            }
        }

        return $suggestions->values();
    }

    private function relatedItems(string $module, string $slug, string $category, int $limit): Collection
    {
        $modelClass = $this->modules->modelClass($module);

        if ($modelClass === null) {
            return collect();
        }

        $primary = collect();

        if ($category !== '') {
            $primary = $this->relatedBaseQuery($modelClass, $module, $slug)
                ->whereHas('category', fn (Builder $query): Builder => $query->where('name', $category))
                ->with($this->eagerLoads($module))
                ->orderByDesc('published_at')
                ->limit(max(6, $limit * 2))
                ->get()
                ->map(fn (Model $record): array => $this->transform($record, $module));
        }

        $fallback = collect();

        if ($primary->count() < $limit) {
            $fallback = $this->relatedBaseQuery($modelClass, $module, $slug)
                ->when($category !== '', function (Builder $query) use ($category): void {
                    $query->where(function (Builder $categoryQuery) use ($category): void {
                        $categoryQuery->whereDoesntHave('category')
                            ->orWhereHas('category', fn (Builder $query): Builder => $query->where('name', '!=', $category));
                    });
                })
                ->with($this->eagerLoads($module))
                ->orderByDesc('published_at')
                ->limit(max(6, $limit * 3))
                ->get()
                ->map(fn (Model $record): array => $this->transform($record, $module));
        }

        return $primary
            ->merge($fallback)
            ->unique(fn (array $item): string => (string) ($item['id'] ?? ''))
            ->take(max(1, $limit))
            ->values();
    }

    private function collectItems(array $filters = []): Collection
    {
        $module = $filters['module'] ?? null;
        $modules = ($module && $this->modules->has($module))
            ? [$module]
            : $this->modules->keys();

        $items = collect();

        foreach ($modules as $mod) {
            $modelClass = $this->modules->modelClass($mod);
            $records = $this->buildQuery($modelClass, $mod, $filters)
                ->with($this->eagerLoads($mod))
                ->get();

            foreach ($records as $record) {
                $items->push($this->transform($record, $mod));
            }
        }

        $price = $filters['price'] ?? 'all';

        if ($price === 'free') {
            return $items->filter(fn ($item) => $item['isFree'])->values();
        }

        if ($price === 'paid') {
            return $items->filter(fn ($item) => ! $item['isFree'])->values();
        }

        return $items->values();
    }

    private function relatedBaseQuery(string $modelClass, string $module, string $slug): Builder
    {
        return $this->buildQuery($modelClass, $module, [])
            ->where('slug', '!=', $slug);
    }

    private function eagerLoads(string $module): array
    {
        $base = [
            'category',
            'organizationProfile.socialLinks',
            'offers' => fn ($q) => $q->where('is_active', true),
        ];

        if ($module === 'evenements') {
            $base[] = 'dates';
        }

        return $base;
    }

    private function buildQuery(string $modelClass, string $module, array $filters): Builder
    {
        $query = $modelClass::query()
            ->where('is_active', true)
            ->whereNotNull('published_at');

        if ($module === 'evenements') {
            $query->withCount('likes');
            $this->excludeEndedEvents($query);
        }

        // Category filter
        if ($cat = ($filters['category'] ?? null)) {
            $query->whereHas('category', fn ($q) => $q->where('name', $cat));
        }

        // Full-text search
        if ($q = ($filters['q'] ?? null)) {
            $titleField = ($module === 'stands') ? 'name' : 'title';
            $query->where(function ($sub) use ($titleField, $q) {
                $sub->where($titleField, 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%");
            });
        }

        // City filter (only Event has city_id)
        if ($city = ($filters['city'] ?? null)) {
            if ($module === 'evenements') {
                $cityId = City::where('name', $city)->value('id');
                if ($cityId) {
                    $query->where('city_id', $cityId);
                }
            }
        }

        $dateFrom = $this->parseDateBoundary($filters['date_from'] ?? null, false);
        $dateTo = $this->parseDateBoundary($filters['date_to'] ?? null, true);

        if ($dateFrom !== null || $dateTo !== null) {
            $this->applyDateFilter($query, $module, $dateFrom, $dateTo);
        }

        // Featured filter
        if (($filters['featured'] ?? null) === 'true') {
            $query->whereJsonContains('meta->featured', true);
        }

        return $query;
    }

    private function applySort(Collection $items, string $sort): Collection
    {
        return match ($sort) {
            'popular' => $items->sortByDesc(fn ($i) => (int) $i['popular'])->values(),
            'price' => $items->sortBy(fn ($i) => $i['priceFrom'])->values(),
            default => $items->sortByDesc(fn ($i) => $i['publishedAt'])->values(),
        };
    }

    private function parseDateBoundary(?string $value, bool $endOfDay): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyDateFilter(Builder $query, string $module, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): void
    {
        if ($module === 'evenements') {
            $query->whereHas('dates', function (Builder $dateQuery) use ($dateFrom, $dateTo): void {
                $this->applyDateOverlap($dateQuery, 'starts_at', 'ends_at', $dateFrom, $dateTo);
            });

            return;
        }

        if ($module === 'appels-a-projets') {
            $this->applyDateOverlap($query, 'application_closes_at', null, $dateFrom, $dateTo);

            return;
        }

        $this->applyDateOverlap($query, 'starts_at', 'ends_at', $dateFrom, $dateTo);
    }

    private function applyDateOverlap(Builder $query, string $startField, ?string $endField, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): void
    {
        if ($dateTo !== null) {
            $query->where($startField, '<=', $dateTo);
        }

        if ($dateFrom === null) {
            return;
        }

        if ($endField === null) {
            $query->where($startField, '>=', $dateFrom);

            return;
        }

        $query->where(function (Builder $dateQuery) use ($startField, $endField, $dateFrom): void {
            $dateQuery->where($endField, '>=', $dateFrom)
                ->orWhere(function (Builder $fallbackQuery) use ($startField, $endField, $dateFrom): void {
                    $fallbackQuery->whereNull($endField)
                        ->where($startField, '>=', $dateFrom);
                });
        });
    }

    private function getCityName(?int $cityId): string
    {
        if ($cityId === null) {
            return '';
        }

        if (! array_key_exists($cityId, $this->cityCache)) {
            $this->cityCache[$cityId] = City::find($cityId)?->name ?? '';
        }

        return $this->cityCache[$cityId];
    }

    private function transformSuggestion(Model $model, string $module, string $query): array
    {
        $title = $module === 'stands' ? (string) ($model->name ?? '') : (string) ($model->title ?? '');
        $normalizedTitle = mb_strtolower(trim($title));
        $normalizedQuery = mb_strtolower(trim($query));
        $presentation = $this->modules->presentation($module);
        $score = str_starts_with($normalizedTitle, $normalizedQuery)
            ? 3
            : (str_contains($normalizedTitle, $normalizedQuery) ? 2 : 1);

        return [
            'title' => $title,
            'href' => sprintf('/%s/%s', $module, $model->slug),
            'module' => $module,
            'moduleTitle' => $presentation['title'],
            'city' => $module === 'evenements' ? $this->getCityName($model->city_id ?? null) : '',
            'category' => (string) ($model->category?->name ?? ''),
            '_score' => $score,
            '_publishedAt' => $model->published_at?->toIso8601String() ?? '',
        ];
    }

    private function transform(Model $model, string $module): array
    {
        $category = $model->category;
        $org = $model->organizationProfile;
        $offers = $model->relationLoaded('offers') ? $model->offers : collect();
        $meta = (array) ($model->meta ?? []);
        $presentation = $this->modules->presentation($module);

        // Title
        $title = ($model instanceof Stand) ? $model->name : $model->title;

        // Pricing
        $minPrice = $offers->min('price_amount') ?? ($model instanceof Stand ? $model->price_amount : 0) ?? 0;
        $isFree = ($minPrice === 0);
        $badges = collect(array_merge([
            $isFree ? 'Gratuit' : 'Payant',
        ], (array) ($meta['badges'] ?? [])))
            ->filter(fn ($badge): bool => is_string($badge) && trim($badge) !== '')
            ->map(fn (string $badge): string => trim($badge))
            ->unique()
            ->values()
            ->all();

        // Currency — fall back to offers, then tenant, then org metadata
        $currency = $model->currency_code
            ?? $offers->first()?->currency_code
            ?? tenant()?->currency_code
            ?? data_get($org?->meta, 'currency_code')
            ?? '';

        // Dates
        [$startsAt, $endsAt] = $this->extractDates($model, $module);

        // City
        $cityName = '';
        if (property_exists($model, 'city_id') || isset($model->city_id)) {
            $cityName = $this->getCityName($model->city_id ?? null);
        }

        // Country
        $country = $model->country_code ?? '';

        // Remaining seats
        $totalQty = $offers->sum('quantity_total');
        $soldQty = $offers->sum('quantity_sold');
        $remaining = $totalQty > 0 ? ($totalQty - $soldQty) : null;

        // Organizer
        $organizers = $org
            ? [['name' => $org->display_name ?? '', 'role' => 'Organisateur', 'imageUrl' => $org->logo_url ?? '']]
            : [];

        // Cover image (not on all models — stored in meta as fallback)
        $coverImageUrl = $model->cover_image_url ?? $meta['cover_image_url'] ?? '';

        return [
            'id' => $model->public_id,
            'module' => $module,
            'moduleTitle' => $presentation['title'],
            'moduleSingular' => $presentation['singular'],
            'moduleCta' => $presentation['cta'],
            'slug' => $model->slug,
            'title' => $title,
            'eyebrow' => $category?->name ?? '',
            'summary' => $model->summary ?? '',
            'description' => $model->description ?? '',
            'category' => $category?->name ?? '',
            'city' => $cityName,
            'country' => $country,
            'venueName' => $model->venue_name ?? null,
            'address' => $model->venue_address ?? null,
            'format' => $meta['format'] ?? null,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'applicationOpensAt' => $model instanceof CallForProject
                ? $model->application_opens_at?->toIso8601String()
                : null,
            'deadlineAt' => $model instanceof CallForProject
                ? $model->application_closes_at?->toIso8601String()
                : null,
            'publishedAt' => $model->published_at?->toIso8601String() ?? '',
            'coverImageUrl' => $coverImageUrl,
            'gallery' => (array) ($meta['gallery'] ?? []),
            'priceFrom' => $minPrice,
            'currency' => $currency,
            'isFree' => $isFree,
            'publicStatus' => $model->public_status_code ?? '',
            'featured' => (bool) ($meta['featured'] ?? false),
            'popular' => (bool) ($meta['popular'] ?? false),
            'likesCount' => (int) ($model->likes_count ?? 0),
            'badges' => $badges,
            'highlights' => (array) ($meta['highlights'] ?? []),
            'organizerSlug' => tenant()?->slug ?? '',
            'organizers' => $organizers,
            'speakers' => (array) ($meta['speakers'] ?? []),
            'stats' => (array) ($meta['stats'] ?? []),
            'tiers' => $offers->map(fn ($o) => $this->transformOffer($o))->values()->all(),
            'timeline' => (array) ($meta['timeline'] ?? []),
            'faq' => (array) ($meta['faq'] ?? []),
            'program' => (array) ($meta['program'] ?? []),
            'conditions' => (array) ($meta['conditions'] ?? []),
            'requiredDocuments' => (array) ($meta['requiredDocuments'] ?? []),
            'progressCurrent' => $model instanceof CrowdfundingCampaign ? $model->raised_amount : null,
            'progressTarget' => $model instanceof CrowdfundingCampaign ? $model->target_amount : null,
            'backers' => $meta['backers'] ?? null,
            'capacity' => $meta['capacity'] ?? null,
            'remainingSeats' => $remaining,
            'applicationForm' => $model instanceof CallForProject
                ? $this->callForProjectApplicationFormService->schemaFor($model)
                : null,
        ];
    }

    private function extractDates(Model $model, string $module): array
    {
        if ($module === 'evenements') {
            $dates = $model->relationLoaded('dates') ? $model->dates : collect();

            return [
                $dates->first()?->starts_at?->toIso8601String(),
                $dates->last()?->ends_at?->toIso8601String(),
            ];
        }

        $startsAt = isset($model->starts_at) ? $model->starts_at?->toIso8601String() : null;
        $endsAt = isset($model->ends_at) ? $model->ends_at?->toIso8601String() : null;

        return [$startsAt, $endsAt];
    }

    private function excludeEndedEvents(Builder $query): void
    {
        $now = CarbonImmutable::now();

        $query->whereHas('dates', function (Builder $dateQuery) use ($now): void {
            $dateQuery->where(function (Builder $activeDateQuery) use ($now): void {
                $activeDateQuery->whereNull('ends_at')
                    ->where('starts_at', '>=', $now);
            })->orWhere(function (Builder $activeDateQuery) use ($now): void {
                $activeDateQuery->whereNotNull('ends_at')
                    ->where('ends_at', '>=', $now);
            });
        });
    }

    private function transformOffer(Offer $offer): array
    {
        $meta = (array) ($offer->meta ?? []);
        $remaining = $offer->quantity_total > 0
            ? $offer->quantity_total - $offer->quantity_sold
            : null;

        return [
            'id' => $offer->public_id,
            'title' => $offer->name,
            'subtitle' => $offer->description,
            'price' => $offer->price_amount,
            'currency' => $offer->currency_code,
            'remaining' => $remaining,
            'quantityLabel' => $remaining !== null ? "{$remaining} restante(s)" : null,
            'ctaLabel' => $meta['ctaLabel'] ?? 'Réserver',
            'perks' => (array) ($meta['perks'] ?? []),
        ];
    }
}
