<?php

namespace App\Services\Public;

use App\Models\CallForProject;
use App\Models\City;
use App\Models\CrowdfundingCampaign;
use App\Models\Event;
use App\Models\Offer;
use App\Models\Stand;
use App\Models\Training;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PublicContentService
{
    private const MODULE_MAP = [
        'evenements'       => Event::class,
        'formations'       => Training::class,
        'stands'           => Stand::class,
        'appels-a-projets' => CallForProject::class,
        'crowdfunding'     => CrowdfundingCampaign::class,
    ];

    private array $cityCache = [];

    // ─── Public API ───────────────────────────────────────────────────────

    public function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $module  = $filters['module'] ?? null;
        $modules = ($module && isset(self::MODULE_MAP[$module]))
            ? [$module]
            : array_keys(self::MODULE_MAP);

        $items = collect();

        foreach ($modules as $mod) {
            $modelClass = self::MODULE_MAP[$mod];
            $records    = $this->buildQuery($modelClass, $mod, $filters)
                ->with($this->eagerLoads($mod))
                ->get();

            foreach ($records as $record) {
                $items->push($this->transform($record, $mod));
            }
        }

        // Price filter (computed after transform)
        $price = $filters['price'] ?? 'all';
        if ($price === 'free') {
            $items = $items->filter(fn($i) => $i['isFree']);
        } elseif ($price === 'paid') {
            $items = $items->filter(fn($i) => ! $i['isFree']);
        }

        // Sort
        $items = $this->applySort($items, $filters['sort'] ?? 'recent');

        $total    = $items->count();
        $paginated = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'items'       => $paginated->all(),
            'total'       => $total,
            'totalPages'  => (int) ceil($total / max(1, $perPage)),
            'currentPage' => $page,
        ];
    }

    public function find(string $module, string $slug): ?array
    {
        $modelClass = self::MODULE_MAP[$module] ?? null;
        if (! $modelClass) {
            return null;
        }

        $record = $modelClass::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with($this->eagerLoads($module))
            ->first();

        return $record ? $this->transform($record, $module) : null;
    }

    public function availableFilters(): array
    {
        $categories = collect();
        $cityIds    = collect();

        foreach (self::MODULE_MAP as $module => $modelClass) {
            $records = $modelClass::query()
                ->where('is_active', true)
                ->with('category')
                ->get(['id', 'category_id', isset((new $modelClass)->city_id) ? 'city_id' : 'id']);

            foreach ($records as $record) {
                if ($record->category) {
                    $categories->push($record->category->name);
                }
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
            'categories' => $categories->unique()->sort()->values()->all(),
            'cities'     => $cityNames->all(),
        ];
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    private function eagerLoads(string $module): array
    {
        $base = [
            'category',
            'organizationProfile.socialLinks',
            'offers' => fn($q) => $q->where('is_active', true),
        ];

        if ($module === 'evenements') {
            $base[] = 'dates';
        }

        return $base;
    }

    private function buildQuery(string $modelClass, string $module, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = $modelClass::query()
            ->where('is_active', true)
            ->whereNotNull('published_at');

        // Category filter
        if ($cat = ($filters['category'] ?? null)) {
            $query->whereHas('category', fn($q) => $q->where('name', $cat));
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

        // Featured filter
        if (($filters['featured'] ?? null) === 'true') {
            $query->whereJsonContains('meta->featured', true);
        }

        return $query;
    }

    private function applySort(Collection $items, string $sort): Collection
    {
        return match ($sort) {
            'popular' => $items->sortByDesc(fn($i) => (int) $i['popular'])->values(),
            'price'   => $items->sortBy(fn($i) => $i['priceFrom'])->values(),
            default   => $items->sortByDesc(fn($i) => $i['publishedAt'])->values(),
        };
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

    private function transform(Model $model, string $module): array
    {
        $category = $model->category;
        $org      = $model->organizationProfile;
        $offers   = $model->relationLoaded('offers') ? $model->offers : collect();
        $meta     = (array) ($model->meta ?? []);

        // Title
        $title = ($model instanceof Stand) ? $model->name : $model->title;

        // Pricing
        $minPrice = $offers->min('price_amount') ?? ($model instanceof Stand ? $model->price_amount : 0) ?? 0;
        $isFree   = ($minPrice === 0);

        // Currency — fall back to org or empty
        $currency = $model->currency_code ?? '';

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
        $totalQty  = $offers->sum('quantity_total');
        $soldQty   = $offers->sum('quantity_sold');
        $remaining = $totalQty > 0 ? ($totalQty - $soldQty) : null;

        // Organizer
        $organizers = $org
            ? [['name' => $org->display_name ?? '', 'role' => 'Organisateur', 'imageUrl' => $org->logo_url ?? '']]
            : [];

        // Cover image (not on all models — stored in meta as fallback)
        $coverImageUrl = $model->cover_image_url ?? $meta['cover_image_url'] ?? '';

        return [
            'id'               => $model->public_id,
            'module'           => $module,
            'slug'             => $model->slug,
            'title'            => $title,
            'eyebrow'          => $category?->name ?? '',
            'summary'          => $model->summary ?? '',
            'description'      => $model->description ?? '',
            'category'         => $category?->name ?? '',
            'city'             => $cityName,
            'country'          => $country,
            'venueName'        => $model->venue_name ?? null,
            'address'          => $model->venue_address ?? null,
            'format'           => $meta['format'] ?? null,
            'startsAt'         => $startsAt,
            'endsAt'           => $endsAt,
            'deadlineAt'       => $model instanceof CallForProject
                ? $model->application_closes_at?->toIso8601String()
                : null,
            'publishedAt'      => $model->published_at?->toIso8601String() ?? '',
            'coverImageUrl'    => $coverImageUrl,
            'gallery'          => (array) ($meta['gallery'] ?? []),
            'priceFrom'        => $minPrice,
            'currency'         => $currency,
            'isFree'           => $isFree,
            'publicStatus'     => $model->public_status_code ?? '',
            'featured'         => (bool) ($meta['featured'] ?? false),
            'popular'          => (bool) ($meta['popular'] ?? false),
            'badges'           => (array) ($meta['badges'] ?? []),
            'highlights'       => (array) ($meta['highlights'] ?? []),
            'organizerSlug'    => tenant()?->slug ?? '',
            'organizers'       => $organizers,
            'speakers'         => (array) ($meta['speakers'] ?? []),
            'stats'            => (array) ($meta['stats'] ?? []),
            'tiers'            => $offers->map(fn($o) => $this->transformOffer($o))->values()->all(),
            'timeline'         => (array) ($meta['timeline'] ?? []),
            'faq'              => (array) ($meta['faq'] ?? []),
            'program'          => (array) ($meta['program'] ?? []),
            'conditions'       => (array) ($meta['conditions'] ?? []),
            'requiredDocuments'=> (array) ($meta['requiredDocuments'] ?? []),
            'progressCurrent'  => $model instanceof CrowdfundingCampaign ? $model->raised_amount : null,
            'progressTarget'   => $model instanceof CrowdfundingCampaign ? $model->target_amount : null,
            'backers'          => $meta['backers'] ?? null,
            'capacity'         => $meta['capacity'] ?? null,
            'remainingSeats'   => $remaining,
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
        $endsAt   = isset($model->ends_at) ? $model->ends_at?->toIso8601String() : null;

        return [$startsAt, $endsAt];
    }

    private function transformOffer(Offer $offer): array
    {
        $meta      = (array) ($offer->meta ?? []);
        $remaining = $offer->quantity_total > 0
            ? $offer->quantity_total - $offer->quantity_sold
            : null;

        return [
            'id'            => $offer->public_id,
            'title'         => $offer->name,
            'subtitle'      => $offer->description,
            'price'         => $offer->price_amount,
            'currency'      => $offer->currency_code,
            'remaining'     => $remaining,
            'quantityLabel' => $remaining !== null ? "{$remaining} restante(s)" : null,
            'ctaLabel'      => $meta['ctaLabel'] ?? 'Réserver',
            'perks'         => (array) ($meta['perks'] ?? []),
        ];
    }
}
