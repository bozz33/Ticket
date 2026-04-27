<?php

namespace App\Services\Tenancy;

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
        return $this->getOrCreate()->load([
            'contacts',
            'socialLinks' => fn ($query) => $query->where('is_public', true),
        ]);
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
