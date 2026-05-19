<?php

namespace Ticket\PublicCatalog\Application;

use App\Enums\TenantStatus;
use App\Models\PublicCatalogItem;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PublicCatalogProjector
{
    public function __construct(
        private readonly PublicContentService $content,
    ) {}

    public function rebuildAll(): array
    {
        $summary = [
            'tenants' => 0,
            'items' => 0,
        ];

        Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use (&$summary): void {
                $summary['tenants']++;
                $summary['items'] += $this->rebuildTenant($tenant);
            });

        return $summary;
    }

    public function rebuildTenant(Tenant $tenant): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        $items = $tenant->run(fn (): array => $this->content->list([], 1, 10000)['items'] ?? []);
        $currentIds = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $module = (string) ($item['module'] ?? '');
            $publicId = (string) ($item['id'] ?? '');

            if ($module === '' || $publicId === '') {
                continue;
            }

            $catalogItem = PublicCatalogItem::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'module' => $module,
                    'item_public_id' => $publicId,
                ],
                $this->attributesFor($tenant, $item),
            );

            $currentIds[] = $catalogItem->getKey();
        }

        $staleQuery = PublicCatalogItem::query()->where('tenant_id', $tenant->getKey());

        if ($currentIds !== []) {
            $staleQuery->whereNotIn('id', $currentIds);
        }

        $staleQuery->delete();

        return count($currentIds);
    }

    private function attributesFor(Tenant $tenant, array $item): array
    {
        return [
            'tenant_public_id' => (string) $tenant->public_id,
            'tenant_slug' => (string) $tenant->slug,
            'tenant_name' => (string) $tenant->name,
            'item_slug' => (string) ($item['slug'] ?? ''),
            'title' => (string) ($item['title'] ?? ''),
            'summary' => (string) ($item['summary'] ?? ''),
            'category' => $this->nullableString($item['category'] ?? null),
            'city' => $this->nullableString($item['city'] ?? null),
            'country_code' => $this->nullableString($item['country'] ?? null),
            'currency_code' => $this->nullableString($item['currency'] ?? null),
            'price_from' => max(0, (int) ($item['priceFrom'] ?? 0)),
            'is_free' => (bool) ($item['isFree'] ?? false),
            'is_featured' => (bool) ($item['featured'] ?? false),
            'popularity_score' => $this->popularityScore($item),
            'published_at' => $this->parseDate($item['publishedAt'] ?? null),
            'starts_at' => $this->parseDate($item['startsAt'] ?? $item['applicationOpensAt'] ?? null),
            'ends_at' => $this->parseDate($item['endsAt'] ?? $item['deadlineAt'] ?? null),
            'search_text' => $this->searchText($tenant, $item),
            'payload' => $item,
        ];
    }

    private function popularityScore(array $item): int
    {
        $score = (int) ($item['likesCount'] ?? 0);

        if ((bool) ($item['popular'] ?? false)) {
            $score += 1000;
        }

        if ((bool) ($item['featured'] ?? false)) {
            $score += 250;
        }

        return $score;
    }

    private function searchText(Tenant $tenant, array $item): string
    {
        $parts = [
            $tenant->name,
            $tenant->slug,
            $item['title'] ?? '',
            $item['summary'] ?? '',
            $item['description'] ?? '',
            $item['category'] ?? '',
            $item['city'] ?? '',
            $item['country'] ?? '',
            $item['moduleTitle'] ?? '',
        ];

        return mb_strtolower(trim(implode(' ', array_filter(array_map(
            fn (mixed $part): string => is_scalar($part) ? (string) $part : '',
            $parts,
        )))));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function tableExists(): bool
    {
        try {
            return Schema::connection(config('ticket.central_connection', 'central'))
                ->hasTable('public_catalog_items');
        } catch (\Throwable) {
            return false;
        }
    }
}
