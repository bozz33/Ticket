<?php

declare(strict_types=1);

namespace App\Support\ReferenceData;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CityReferenceSearchService
{
    /**
     * @return array<int, \App\Models\City>
     */
    public function search(?string $countryCode = null, ?string $query = null, int $limit = 50, bool $onlyActive = true): array
    {
        $countryCode = $this->normalizeCountryCode($countryCode);
        $query = $this->normalizeQuery($query);
        $limit = max(1, min($limit, 100));

        $builder = City::query()
            ->with('country')
            ->when($onlyActive, fn (Builder $cityQuery) => $cityQuery->where('is_active', true))
            ->when($countryCode !== null, function (Builder $cityQuery) use ($countryCode): void {
                $cityQuery->whereHas('country', fn (Builder $countryQuery) => $countryQuery->where('iso2', $countryCode));
            })
            ->when($query !== null, function (Builder $cityQuery) use ($query): void {
                $search = Str::lower(Str::ascii($query));
                $cityQuery->where(function (Builder $nested) use ($query, $search): void {
                    $nested
                        ->whereRaw('LOWER(name) LIKE ?', [sprintf('%s%%', Str::lower($query))])
                        ->orWhereRaw('LOWER(name) LIKE ?', [sprintf('%%%s%%', Str::lower($query))])
                        ->orWhereRaw('LOWER(slug) LIKE ?', [sprintf('%s%%', Str::slug($search))])
                        ->orWhereRaw('LOWER(slug) LIKE ?', [sprintf('%%%s%%', Str::slug($search))]);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($query !== null ? $limit : min($limit, 100));

        return $builder->get()->all();
    }

    private function normalizeCountryCode(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeQuery(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
