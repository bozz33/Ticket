<?php

declare(strict_types=1);

namespace App\Support\ReferenceData;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class CountryReferenceImporter
{
    private const EXTRA_CITIES = [
        'BE' => ['Bruxelles', 'Anvers'],
        'BF' => ['Ouagadougou', 'Bobo-Dioulasso'],
        'BJ' => ['Cotonou', 'Porto-Novo'],
        'CA' => ['Toronto', 'Montréal', 'Ottawa', 'Vancouver'],
        'CD' => ['Kinshasa', 'Lubumbashi'],
        'CG' => ['Brazzaville', 'Pointe-Noire'],
        'CI' => ['Abidjan', 'Yamoussoukro', 'Bouaké', 'San-Pédro', 'Daloa'],
        'CM' => ['Douala', 'Yaoundé'],
        'DE' => ['Berlin', 'Munich', 'Hambourg'],
        'FR' => ['Paris', 'Lyon', 'Marseille'],
        'GB' => ['London', 'Manchester', 'Birmingham'],
        'GH' => ['Accra', 'Kumasi'],
        'GN' => ['Conakry', 'Kankan'],
        'MA' => ['Casablanca', 'Rabat', 'Marrakech'],
        'ML' => ['Bamako', 'Sikasso'],
        'NE' => ['Niamey', 'Zinder'],
        'NG' => ['Lagos', 'Abuja', 'Port Harcourt'],
        'SN' => ['Dakar', 'Thiès'],
        'TG' => ['Lomé', 'Sokodé'],
        'US' => ['New York', 'Los Angeles', 'Chicago', 'Washington'],
    ];

    private const MANAGED_CITY_SOURCES = [
        'reference_world_import',
        'reference_country_import',
    ];

    public function importFromFile(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException(sprintf('Reference dataset not found: %s', $absolutePath));
        }

        $decoded = json_decode((string) file_get_contents($absolutePath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Reference dataset is invalid: %s', $absolutePath));
        }

        $importedCountries = 0;
        $importedCities = 0;

        foreach ($decoded as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $iso2 = strtoupper(trim((string) Arr::get($entry, 'iso2')));
            $name = trim((string) Arr::get($entry, 'name'));

            if ($iso2 === '' || $name === '') {
                continue;
            }

            $country = $this->upsertCountry($entry, $index);
            $cityImport = $this->importCitiesForCountry($country, $entry);

            $importedCountries++;
            $importedCities += $cityImport['processed'];
        }

        return [
            'countries' => $importedCountries,
            'cities' => $importedCities,
            'active_countries_total' => Country::query()->where('is_active', true)->count(),
            'active_cities_total' => City::query()->where('is_active', true)->count(),
        ];
    }

    private function upsertCountry(array $entry, int $index): Country
    {
        $iso2 = strtoupper(trim((string) Arr::get($entry, 'iso2')));

        return Country::query()->updateOrCreate(
            ['iso2' => $iso2],
            [
                'iso3' => $this->nullableString(Arr::get($entry, 'iso3'), 3),
                'name' => trim((string) Arr::get($entry, 'name')),
                'phone_code' => $this->normalizePhoneCode(Arr::get($entry, 'phonecode')),
                'currency_code' => $this->nullableString(Arr::get($entry, 'currency'), 3),
                'language_code' => null,
                'sort_order' => $index + 1,
                'is_active' => true,
                'meta' => array_filter([
                    'source_country_id' => Arr::get($entry, 'id'),
                    'capital' => $this->nullableString(Arr::get($entry, 'capital')),
                    'native' => $this->nullableString(Arr::get($entry, 'native')),
                    'region' => $this->nullableString(Arr::get($entry, 'region')),
                    'subregion' => $this->nullableString(Arr::get($entry, 'subregion')),
                    'emoji' => $this->nullableString(Arr::get($entry, 'emoji')),
                    'emoji_u' => $this->nullableString(Arr::get($entry, 'emojiU')),
                    'nationality' => $this->nullableString(Arr::get($entry, 'nationality')),
                    'latitude' => $this->nullableString(Arr::get($entry, 'latitude')),
                    'longitude' => $this->nullableString(Arr::get($entry, 'longitude')),
                ], static fn ($value) => $value !== null && $value !== ''),
            ],
        );
    }

    private function importCitiesForCountry(Country $country, array $entry): array
    {
        $capitalNames = $this->capitalNames((string) Arr::get($entry, 'capital'));
        $states = Arr::get($entry, 'states');
        $existingCities = $this->existingCitiesIndex($country);

        if (is_array($states) && $states !== []) {
            $result = $this->importNestedCitiesForCountry($country, $entry, $states, $capitalNames, $existingCities);
        } else {
            $result = $this->importFallbackCitiesForCountry($country, $entry, $capitalNames, $existingCities);
        }

        $activeManagedCityIds = array_values(array_unique($result['active_city_ids']));

        if ($existingCities['managed_ids'] !== []) {
            $staleQuery = City::query()
                ->where('country_id', $country->id)
                ->whereIn('id', $existingCities['managed_ids']);

            if ($activeManagedCityIds !== []) {
                $staleQuery->whereNotIn('id', $activeManagedCityIds);
            }

            $staleQuery->update(['is_active' => false]);
        }

        return [
            'processed' => $result['processed'],
            'active_city_ids' => $activeManagedCityIds,
        ];
    }

    private function importNestedCitiesForCountry(Country $country, array $entry, array $states, Collection $capitalNames, array $existingCities): array
    {
        $imported = 0;
        $activeCityIds = [];
        $usedSlugs = [];
        $cityIndex = 0;

        foreach ($states as $state) {
            if (! is_array($state)) {
                continue;
            }

            $stateName = $this->nullableString(Arr::get($state, 'name'));
            $stateCode = $this->nullableString(Arr::get($state, 'iso2'));
            $stateCities = Arr::get($state, 'cities');

            if (! is_array($stateCities)) {
                continue;
            }

            foreach ($stateCities as $cityEntry) {
                if (! is_array($cityEntry)) {
                    continue;
                }

                $cityName = trim((string) Arr::get($cityEntry, 'name'));

                if ($cityName === '') {
                    continue;
                }

                $slug = $this->uniqueCitySlug(
                    country: $country,
                    cityName: $cityName,
                    stateCode: $stateCode,
                    stateName: $stateName,
                    sourceCityId: Arr::get($cityEntry, 'id'),
                    existingCitiesBySlug: $existingCities['by_slug'],
                    usedSlugs: $usedSlugs,
                );

                $city = City::query()->updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $cityName,
                        'sort_order' => ++$cityIndex,
                        'is_active' => true,
                        'meta' => array_filter([
                            'source' => 'reference_world_import',
                            'source_city_id' => Arr::get($cityEntry, 'id'),
                            'source_state_id' => Arr::get($state, 'id'),
                            'state_name' => $stateName,
                            'state_code' => $stateCode,
                            'latitude' => $this->nullableString(Arr::get($cityEntry, 'latitude')),
                            'longitude' => $this->nullableString(Arr::get($cityEntry, 'longitude')),
                            'timezone' => $this->nullableString(Arr::get($cityEntry, 'timezone')),
                            'is_capital' => $capitalNames->contains(fn (string $capital) => Str::lower(Str::ascii($capital)) === Str::lower(Str::ascii($cityName))),
                        ], static fn ($value) => $value !== null && $value !== ''),
                    ],
                );

                $activeCityIds[] = (int) $city->id;
                $existingCities['by_slug'][$slug] = [
                    'id' => (int) $city->id,
                    'managed' => true,
                ];
                $imported++;
            }
        }

        foreach ($capitalNames as $capitalName) {
            $existingCapital = City::query()
                ->where('country_id', $country->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower($capitalName)])
                ->first(['id', 'meta']);

            if ($existingCapital) {
                if ($this->isManagedCitySource((string) data_get($existingCapital->meta ?? [], 'source'))) {
                    $activeCityIds[] = (int) $existingCapital->id;
                }

                continue;
            }

            $slug = $this->uniqueCitySlug(
                country: $country,
                cityName: $capitalName,
                stateCode: null,
                stateName: null,
                sourceCityId: null,
                existingCitiesBySlug: $existingCities['by_slug'],
                usedSlugs: $usedSlugs,
            );

            $city = City::query()->updateOrCreate(
                [
                    'country_id' => $country->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $capitalName,
                    'sort_order' => ++$cityIndex,
                    'is_active' => true,
                    'meta' => [
                        'source' => 'reference_world_import',
                        'is_capital' => true,
                    ],
                ],
            );

            $activeCityIds[] = (int) $city->id;
            $existingCities['by_slug'][$slug] = [
                'id' => (int) $city->id,
                'managed' => true,
            ];
            $imported++;
        }

        return [
            'processed' => $imported,
            'active_city_ids' => $activeCityIds,
        ];
    }

    private function importFallbackCitiesForCountry(Country $country, array $entry, Collection $capitalNames, array $existingCities): array
    {
        $imported = 0;
        $activeCityIds = [];
        $usedSlugs = [];

        $cityNames = $this->fallbackCityNamesForCountry(
            strtoupper((string) $country->iso2),
            (string) $country->name,
            $capitalNames,
        );

        foreach ($cityNames as $cityIndex => $cityName) {
            $slug = $this->uniqueCitySlug(
                country: $country,
                cityName: $cityName,
                stateCode: null,
                stateName: null,
                sourceCityId: null,
                existingCitiesBySlug: $existingCities['by_slug'],
                usedSlugs: $usedSlugs,
            );

            $city = City::query()->updateOrCreate(
                [
                    'country_id' => $country->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $cityName,
                    'sort_order' => $cityIndex + 1 + $imported,
                    'is_active' => true,
                    'meta' => [
                        'source' => 'reference_country_import',
                        'is_capital' => $capitalNames->contains(fn (string $capital) => Str::lower(Str::ascii($capital)) === Str::lower(Str::ascii($cityName))),
                    ],
                ],
            );

            $activeCityIds[] = (int) $city->id;
            $existingCities['by_slug'][$slug] = [
                'id' => (int) $city->id,
                'managed' => true,
            ];
            $imported++;
        }

        return [
            'processed' => $imported,
            'active_city_ids' => $activeCityIds,
        ];
    }

    private function existingCitiesIndex(Country $country): array
    {
        $bySlug = [];
        $managedIds = [];

        foreach (City::query()->where('country_id', $country->id)->get(['id', 'slug', 'meta']) as $city) {
            $isManaged = $this->isManagedCitySource((string) data_get($city->meta ?? [], 'source'));

            $bySlug[(string) $city->slug] = [
                'id' => (int) $city->id,
                'managed' => $isManaged,
            ];

            if ($isManaged) {
                $managedIds[] = (int) $city->id;
            }
        }

        return [
            'by_slug' => $bySlug,
            'managed_ids' => $managedIds,
        ];
    }

    private function capitalNames(string $capital): Collection
    {
        return collect(explode(',', $capital))
            ->map(static fn (string $value) => trim($value))
            ->filter(static fn (string $value) => $value !== '')
            ->unique(static fn (string $value) => Str::lower(Str::ascii($value)))
            ->values();
    }

    private function fallbackCityNamesForCountry(string $iso2, string $countryName, Collection $capitalNames): array
    {
        $extras = collect(self::EXTRA_CITIES[$iso2] ?? []);

        return $capitalNames
            ->merge($extras)
            ->push($countryName)
            ->unique(static fn (string $value) => Str::lower(Str::ascii($value)))
            ->values()
            ->all();
    }

    private function uniqueCitySlug(
        Country $country,
        string $cityName,
        ?string $stateCode,
        ?string $stateName,
        mixed $sourceCityId,
        array $existingCitiesBySlug,
        array &$usedSlugs,
    ): string {
        $baseSlug = Str::slug($cityName);

        if ($baseSlug === '') {
            $baseSlug = 'city';
        }

        $candidates = [
            $baseSlug,
            $stateCode ? sprintf('%s-%s', $baseSlug, Str::slug($stateCode)) : null,
            $stateName ? sprintf('%s-%s', $baseSlug, Str::slug($stateName)) : null,
            $sourceCityId ? sprintf('%s-%s', $baseSlug, $sourceCityId) : null,
        ];

        foreach (array_filter($candidates) as $candidate) {
            $existingCity = $existingCitiesBySlug[$candidate] ?? null;

            if (! in_array($candidate, $usedSlugs, true) && ($existingCity === null || (($existingCity['managed'] ?? false) === true))) {
                $usedSlugs[] = $candidate;

                return $candidate;
            }
        }

        $suffix = 2;

        do {
            $candidate = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        } while (
            in_array($candidate, $usedSlugs, true)
            || (isset($existingCitiesBySlug[$candidate]) && (($existingCitiesBySlug[$candidate]['managed'] ?? false) === false))
        );

        $usedSlugs[] = $candidate;

        return $candidate;
    }

    private function isManagedCitySource(?string $source): bool
    {
        return in_array(trim((string) $source), self::MANAGED_CITY_SOURCES, true);
    }

    private function normalizePhoneCode(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function nullableString(mixed $value, ?int $maxLength = null): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return $maxLength !== null ? Str::limit($normalized, $maxLength, '') : $normalized;
    }
}
