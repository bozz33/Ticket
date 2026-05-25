<?php

namespace Ticket\PublicCatalog\Application;

use App\Models\PublicCatalogItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Ticket\PublicCatalog\Domain\PublicCatalogModules;

class PublicCatalogProjectionReader
{
    private ?bool $ready = null;
    private ?bool $hasEngagementColumns = null;

    public function __construct(
        private readonly PublicCatalogModules $modules,
    ) {}

    public function isReady(): bool
    {
        if ($this->ready !== null) {
            return $this->ready;
        }

        try {
            $this->ready = Schema::connection(config('ticket.central_connection', 'central'))
                ->hasTable('public_catalog_items')
                && PublicCatalogItem::query()->exists();
        } catch (\Throwable) {
            $this->ready = false;
        }

        return $this->ready;
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 12): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $query = $this->query($filters);
        $total = (clone $query)->count();
        $items = $this->applySort($query, (string) ($filters['sort'] ?? 'recent'))
            ->forPage(max(1, $page), max(1, $perPage))
            ->get()
            ->map(fn (PublicCatalogItem $item): array => $this->payload($item))
            ->values();

        return [
            'items' => $items->all(),
            'total' => $total,
            'totalPages' => (int) ceil($total / max(1, $perPage)),
            'currentPage' => max(1, $page),
        ];
    }

    public function find(string $module, string $slug, ?string $tenantSlug = null): ?array
    {
        if (! $this->isReady() || ! $this->modules->has($module)) {
            return null;
        }

        $query = PublicCatalogItem::query()
            ->where('module', $module)
            ->where('item_slug', $slug);

        $this->excludeEndedContent($query);
        $this->applyTenantFilter($query, $tenantSlug);

        $item = $query
            ->orderByDesc('published_at')
            ->first();

        return $item ? $this->payload($item) : null;
    }

    public function availableFilters(?string $module = null): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $query = PublicCatalogItem::query();
        $this->excludeEndedContent($query);

        if (is_string($module) && $this->modules->has($module)) {
            $query->where('module', $module);
        }

        return [
            'categories' => (clone $query)
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->filter()
                ->values()
                ->all(),
            'cities' => (clone $query)
                ->whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public function summarySnapshot(): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $query = PublicCatalogItem::query();
        $this->excludeEndedContent($query);

        $items = $this->payloads($query->orderByDesc('published_at'));
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

    public function groupedOverview(string $field): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $query = PublicCatalogItem::query()->whereNotNull($field);
        $this->excludeEndedContent($query);

        return $this->payloads($query->orderBy($field))
            ->filter(fn (array $item): bool => filled($item[$field === 'category' ? 'category' : 'city'] ?? null))
            ->groupBy(fn (array $item): string => (string) $item[$field === 'category' ? 'category' : 'city'])
            ->sortKeys()
            ->map(fn (Collection $items, string $value): array => [
                $field === 'category' ? 'category' : 'city' => $value,
                'count' => $items->count(),
                'sample' => $items->first() ?: null,
            ])
            ->values()
            ->all();
    }

    public function speakerHighlights(?int $limit = null): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $query = PublicCatalogItem::query();
        $this->excludeEndedContent($query);

        $speakers = $this->payloads($this->applySort($query, 'popular'))
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

        return $limit !== null && $limit > 0
            ? $speakers->take($limit)->values()->all()
            : $speakers->all();
    }

    public function searchSuggestions(string $query, ?string $module = null, int $limit = 6): ?array
    {
        $normalizedQuery = trim($query);

        if (! $this->isReady() || $normalizedQuery === '' || mb_strlen($normalizedQuery) < 2) {
            return null;
        }

        $records = $this->query([
            'module' => $module,
            'q' => $normalizedQuery,
        ])
            ->orderByDesc('published_at')
            ->limit(max(20, $limit * 4))
            ->get();

        return $records
            ->map(fn (PublicCatalogItem $item): array => $this->suggestionPayload($item, $normalizedQuery))
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

    public function related(string $module, string $slug, int $limit = 3, ?string $tenantSlug = null): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $target = $this->find($module, $slug, $tenantSlug);

        if ($target === null) {
            return [];
        }

        $query = PublicCatalogItem::query()
            ->where('module', $module)
            ->where('item_slug', '!=', $slug);

        $this->excludeEndedContent($query);
        $this->applyTenantFilter($query, $tenantSlug ?: (string) ($target['organizerSlug'] ?? ''));

        $category = (string) ($target['category'] ?? '');

        if ($category !== '') {
            $query->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$category]);
        }

        return $this->payloads(
            $query->orderByDesc('published_at')->limit(max(1, $limit)),
        )->values()->all();
    }

    private function query(array $filters = []): Builder
    {
        $query = PublicCatalogItem::query();
        $module = $filters['module'] ?? null;

        if (($filters['include_past'] ?? null) !== 'true') {
            $this->excludeEndedContent($query);
        }

        if (is_string($module) && $this->modules->has($module)) {
            $query->where('module', $module);
        }

        if ($q = trim((string) ($filters['q'] ?? ''))) {
            $query->where('search_text', 'like', '%'.mb_strtolower($q).'%');
        }

        if ($category = trim((string) ($filters['category'] ?? ''))) {
            $query->where('category', $category);
        }

        if ($city = trim((string) ($filters['city'] ?? ''))) {
            $query->where('city', $city);
        }

        if (($filters['price'] ?? 'all') === 'free') {
            $query->where('is_free', true);
        }

        if (($filters['price'] ?? 'all') === 'paid') {
            $query->where('is_free', false);
        }

        if (($filters['featured'] ?? null) === 'true') {
            $query->where('is_featured', true);
        }

        if ($dateFrom = $this->parseDate($filters['date_from'] ?? null)) {
            $query->where(function (Builder $dateQuery) use ($dateFrom): void {
                $dateQuery->whereNull('ends_at')
                    ->where('starts_at', '>=', $dateFrom)
                    ->orWhere('ends_at', '>=', $dateFrom);
            });
        }

        if ($dateTo = $this->parseDate($filters['date_to'] ?? null)) {
            $query->where(function (Builder $dateQuery) use ($dateTo): void {
                $dateQuery->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $dateTo);
            });
        }

        return $query;
    }

    private function excludeEndedContent(Builder $query): void
    {
        $now = CarbonImmutable::now();

        $query->where(function (Builder $activeQuery) use ($now): void {
            $activeQuery->where(function (Builder $withEndQuery) use ($now): void {
                $withEndQuery->whereNotNull('ends_at')
                    ->where('ends_at', '>=', $now);
            })->orWhere(function (Builder $withoutEndQuery) use ($now): void {
                $withoutEndQuery->whereNull('ends_at')
                    ->where(function (Builder $startsQuery) use ($now): void {
                        $startsQuery->whereNull('starts_at')
                            ->orWhere('starts_at', '>=', $now);
                    });
            });
        });
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'weekly_likes' => $this->hasEngagementColumns()
                ? $query
                    ->orderByDesc('weekly_likes_count')
                    ->orderByDesc('likes_count')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                : $query->orderByDesc('popularity_score')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('popularity_score')->orderByDesc('published_at'),
            'price' => $query->orderBy('price_from')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    private function hasEngagementColumns(): bool
    {
        if ($this->hasEngagementColumns !== null) {
            return $this->hasEngagementColumns;
        }

        try {
            $connection = config('ticket.central_connection', 'central');
            $this->hasEngagementColumns = Schema::connection($connection)->hasColumn('public_catalog_items', 'likes_count')
                && Schema::connection($connection)->hasColumn('public_catalog_items', 'weekly_likes_count');
        } catch (\Throwable) {
            $this->hasEngagementColumns = false;
        }

        return $this->hasEngagementColumns;
    }

    private function applyTenantFilter(Builder $query, ?string $tenantSlug): void
    {
        if (! is_string($tenantSlug) || trim($tenantSlug) === '') {
            return;
        }

        $tenantSlug = trim($tenantSlug);

        $query->where(function (Builder $tenantQuery) use ($tenantSlug): void {
            $tenantQuery->where('tenant_slug', $tenantSlug)
                ->orWhere('tenant_public_id', $tenantSlug);
        });
    }

    private function payloads(Builder $query): Collection
    {
        return $query
            ->get()
            ->map(fn (PublicCatalogItem $item): array => $this->payload($item))
            ->values();
    }

    private function payload(PublicCatalogItem $item): array
    {
        return (array) $item->payload;
    }

    private function suggestionPayload(PublicCatalogItem $item, string $query): array
    {
        $payload = $this->payload($item);
        $title = (string) ($payload['title'] ?? $item->title);
        $normalizedTitle = mb_strtolower(trim($title));
        $normalizedQuery = mb_strtolower(trim($query));
        $presentation = $this->modules->presentation($item->module);
        $score = str_starts_with($normalizedTitle, $normalizedQuery)
            ? 3
            : (str_contains($normalizedTitle, $normalizedQuery) ? 2 : 1);

        return [
            'title' => $title,
            'href' => sprintf('/%s/%s', $item->module, $item->item_slug),
            'module' => $item->module,
            'moduleTitle' => $presentation['title'],
            'city' => (string) ($payload['city'] ?? $item->city ?? ''),
            'category' => (string) ($payload['category'] ?? $item->category ?? ''),
            '_score' => $score,
            '_publishedAt' => $item->published_at?->toIso8601String() ?? '',
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
