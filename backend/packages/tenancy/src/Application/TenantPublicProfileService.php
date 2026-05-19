<?php

namespace Ticket\Tenancy\Application;

use App\Models\Country;
use App\Models\OrganizationProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TenantPublicProfileService
{
    public function getOrCreate(): OrganizationProfile
    {
        return OrganizationProfile::query()->firstOrCreate([], []);
    }

    public function getPublicProjection(): OrganizationProfile
    {
        return $this->getOrCreate()->loadCount('followers')->load([
            'contacts',
            'socialLinks' => fn ($query) => $query->where('is_public', true),
        ]);
    }

    public function getPublicViewData(?array $tenant = null, array $items = []): array
    {
        $profile = $this->getPublicProjection();
        $meta = (array) ($profile->meta ?? []);
        $fallbackItem = $items[0] ?? [];
        $fallbackOrganizer = (is_array($fallbackItem) ? ($fallbackItem['organizers'][0] ?? null) : null);
        $countryCode = strtoupper(trim((string) ($profile->country_code ?? '')));

        return [
            'slug' => (string) ($tenant['slug'] ?? ''),
            'name' => $this->firstFilled([
                $profile->display_name,
                $tenant['name'] ?? null,
                $profile->legal_name,
                $tenant['slug'] ?? null,
            ]),
            'legalName' => $this->firstFilled([
                $profile->legal_name,
                $profile->display_name,
                $tenant['name'] ?? null,
            ]),
            'tagline' => $this->firstFilled([
                data_get($meta, 'tagline'),
                $profile->description,
                $profile->display_name,
                $tenant['name'] ?? null,
            ]),
            'description' => $this->firstFilled([
                $profile->description,
                data_get($meta, 'summary'),
                $profile->display_name,
                $tenant['name'] ?? null,
            ]),
            'city' => (string) ($profile->city ?? ''),
            'country' => $this->resolveCountryName($countryCode),
            'verified' => (bool) data_get($meta, 'verified', false),
            'followers' => (int) ($profile->followers_count ?? data_get($meta, 'followers', 0)),
            'accentColor' => (string) ($profile->primary_color ?: '#0f172a'),
            'logoUrl' => $this->firstFilled([
                $profile->logo_url,
                is_array($fallbackOrganizer) ? ($fallbackOrganizer['imageUrl'] ?? null) : null,
                is_array($fallbackItem) ? ($fallbackItem['coverImageUrl'] ?? null) : null,
            ]),
            'bannerUrl' => $this->firstFilled([
                $profile->banner_url,
                is_array($fallbackItem) ? ($fallbackItem['coverImageUrl'] ?? null) : null,
            ]),
            'websiteUrl' => $this->nullableString($profile->website_url),
            'supportEmail' => $this->firstFilled([
                $profile->email,
                $this->contactValue($profile, 'email'),
                'support@ticket.africa',
            ]),
            'supportPhone' => $this->firstFilled([
                $profile->phone,
                $this->contactValue($profile, 'phone'),
                '+225 27 22 40 11 00',
            ]),
            'socialLinks' => $profile->socialLinks
                ->map(function ($socialLink): ?array {
                    $label = $this->firstFilled([
                        $socialLink->label,
                        $socialLink->platform,
                        $socialLink->url,
                    ]);
                    $url = trim((string) ($socialLink->url ?? ''));

                    if ($label === '' || $url === '') {
                        return null;
                    }

                    return [
                        'label' => $label,
                        'url' => $url,
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function contactValue(OrganizationProfile $profile, string $type): string
    {
        $contact = $profile->contacts->first(fn ($entry) => strtolower((string) ($entry->type ?? '')) === $type);

        return trim((string) ($contact->value ?? ''));
    }

    private function resolveCountryName(string $countryCode): string
    {
        if ($countryCode === '') {
            return '';
        }

        return (string) (Country::query()->where('iso2', $countryCode)->value('name') ?? $countryCode);
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $normalized = trim((string) ($value ?? ''));

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    public function update(array $payload): OrganizationProfile
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($payload): OrganizationProfile {
            $profile = $this->getOrCreate();

            $profile->fill(Arr::only($payload, [
                'legal_name',
                'display_name',
                'description',
                'email',
                'phone',
                'website_url',
                'logo_url',
                'banner_url',
                'primary_color',
                'secondary_color',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country_code',
                'meta',
            ]));
            $profile->save();

            if (array_key_exists('contacts', $payload)) {
                $profile->contacts()->delete();
                foreach ($payload['contacts'] ?? [] as $contact) {
                    $profile->contacts()->create([
                        'type' => $contact['type'] ?? 'general',
                        'label' => $contact['label'] ?? null,
                        'value' => $contact['value'],
                        'is_primary' => (bool) ($contact['is_primary'] ?? false),
                        'sort_order' => $contact['sort_order'] ?? 0,
                        'meta' => $contact['meta'] ?? [],
                    ]);
                }
            }

            if (array_key_exists('social_links', $payload)) {
                $profile->socialLinks()->delete();
                foreach ($payload['social_links'] ?? [] as $socialLink) {
                    $profile->socialLinks()->create([
                        'platform' => $socialLink['platform'],
                        'label' => $socialLink['label'] ?? null,
                        'url' => $socialLink['url'],
                        'is_public' => (bool) ($socialLink['is_public'] ?? true),
                        'sort_order' => $socialLink['sort_order'] ?? 0,
                        'meta' => $socialLink['meta'] ?? [],
                    ]);
                }
            }

            return $profile->fresh(['contacts', 'socialLinks']);
        });
    }
}
