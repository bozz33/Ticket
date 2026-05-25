<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use App\Models\Language;
use App\Models\TranslationEntry;
use App\Services\FeatureFlagService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ticket\Payments\Domain\PaymentStatuses;
use Ticket\PublicCatalog\Contracts\FrontContent;

class PublicPlatformConfigurationController extends Controller
{
    private const DEFAULT_MENUS = [
        'header_primary' => [],
        'header_utility' => [],
        'footer_explore' => [],
        'footer_platform' => [],
        'footer_bottom' => [],
    ];

    public function show(
        PlatformSettingsService $platformSettingsService,
        FeatureFlagService $featureFlagService,
        FrontContent $frontContent,
    ): JsonResponse {
        return $this->__invoke($platformSettingsService, $featureFlagService, $frontContent);
    }

    public function __invoke(
        PlatformSettingsService $platformSettingsService,
        FeatureFlagService $featureFlagService,
        FrontContent $frontContent,
    ): JsonResponse {
        $settings = $platformSettingsService->grouped(publicOnly: true);
        $menus = array_replace($frontContent->defaultMenus(), $frontContent->publicMenus());
        $featureFlags = $featureFlagService->publicFlags();
        $publicUsersCount = (int) PlatformTransaction::query()
            ->whereIn('type', ['public_checkout', 'gateway_charge'])
            ->whereIn('status', PaymentStatuses::successful())
            ->whereRaw("COALESCE(meta->'checkout'->>'buyer_email', meta->'gateway_payload'->'data'->'metadata'->>'buyer_email', meta->'data'->'metadata'->>'buyer_email', '') <> ''")
            ->selectRaw("COUNT(DISTINCT COALESCE(meta->'checkout'->>'buyer_email', meta->'gateway_payload'->'data'->'metadata'->>'buyer_email', meta->'data'->'metadata'->>'buyer_email')) as aggregate")
            ->value('aggregate');

        $defaultTenant = Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->orderByDesc('activated_at')
            ->orderBy('id')
            ->first(['public_id', 'name', 'slug']);

        $languages = $this->publicLanguages();
        $normalized = $this->normalizedConfiguration(
            $settings,
            $menus,
            $featureFlags->pluck('code')->values()->all(),
            $publicUsersCount,
            $defaultTenant?->only(['public_id', 'name', 'slug']),
            $languages,
            $this->publicTranslations(),
        );

        $payload = [
            'data' => $normalized,
            'settings' => $settings,
            'menus' => $menus,
            'feature_flags' => $featureFlags,
            'stats' => [
                'users_count' => $publicUsersCount,
            ],
            'default_tenant' => $defaultTenant?->only(['public_id', 'name', 'slug']),
        ];

        return response()->json($payload, 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function normalizedConfiguration(
        array $settings,
        array $menus,
        array $featureFlags,
        int $usersCount,
        ?array $defaultTenant,
        array $languages,
        array $translations,
    ): array
    {
        $branding = $settings['branding'] ?? [];
        $support = $settings['support'] ?? [];
        $payments = $settings['payments'] ?? [];
        $navigation = $settings['navigation'] ?? [];
        $footer = $settings['footer'] ?? [];
        $seo = $settings['seo'] ?? [];
        $seoDefaults = (array) ($seo['seo.defaults'] ?? []);
        $seoOpenGraph = (array) ($seo['seo.open_graph'] ?? $seo['open_graph'] ?? []);
        $seoTwitter = (array) ($seo['seo.twitter'] ?? $seo['twitter'] ?? []);
        $seoRobots = (array) ($seo['seo.robots'] ?? $seo['robots'] ?? []);
        $seoStructuredData = (array) ($seo['seo.structured_data'] ?? []);
        $seoSitemap = (array) ($seo['seo.sitemap'] ?? []);

        $seoSocialLinks = $this->firstArray([
            data_get($seo['seo.social_links'] ?? [], 'social_links'),
            data_get($seoDefaults, 'social_links'),
        ]);
        $footerSocialLinks = $this->groupValue($footer, 'social_links', ['public_footer', 'footer_content']);

        return [
            'brandName' => $this->firstString([
                $this->groupValue($branding, 'platform_name', ['site_identity', 'brand', 'public_branding']),
                config('app.name'),
                'Ticket',
            ]),
            'logoUrl' => $this->publicAssetUrl($this->groupValue($branding, 'logo_url', ['site_identity', 'brand', 'public_branding'])),
            'faviconUrl' => $this->publicAssetUrl($this->groupValue($branding, 'favicon_url', ['site_identity', 'brand', 'public_branding'])),
            'appleTouchIconUrl' => $this->publicAssetUrl($this->groupValue($branding, 'apple_touch_icon_url', ['site_identity', 'brand', 'public_branding'])),
            'footerDescription' => $this->firstString([
                $this->groupValue($footer, 'description', ['public_footer', 'footer_content']),
                'Catalogue public unifie pour decouvrir, comparer et convertir sur plusieurs modules metier.',
            ]),
            'supportEmail' => $this->firstString([
                $this->groupValue($support, 'email', ['public_contacts', 'contact', 'public_support']),
                'support@ticket.africa',
            ]),
            'supportPhone' => $this->firstString([
                $this->groupValue($support, 'phone', ['public_contacts', 'contact', 'public_support']),
                '+225 27 22 40 11 00',
            ]),
            'currencyCode' => $this->firstString([
                $this->groupValue($payments, 'currency', ['public_catalog', 'catalog', 'checkout']),
                'XOF',
            ]),
            'accountUrl' => $this->firstString([
                $this->groupValue($navigation, 'account_url', ['public_links', 'links', 'header']),
                '/compte',
            ]),
            'organizerCtaUrl' => $this->firstString([
                $this->groupValue($navigation, 'organizer_cta_url', ['public_links', 'links', 'header']),
                '/devenir-organisateur',
            ]),
            'reassuranceItems' => $this->stringArray($this->groupValue($footer, 'reassurance_items', ['public_footer', 'footer_content'])) ?: [
                'Paiement securise et verification serveur',
                'Billets et confirmations centralises',
                'Parcours mobile optimise jusqu\'au checkout',
            ],
            'paymentMethods' => $this->stringArray($this->groupValue($payments, 'methods', ['public_catalog', 'catalog', 'checkout'])) ?: [
                'Carte bancaire',
                'Mobile Money',
            ],
            'socialLinks' => $this->socialLinks($seoSocialLinks ?: $footerSocialLinks),
            'seo' => [
                'defaultTitle' => $this->firstString([
                    data_get($seoDefaults, 'title'),
                    data_get($seo['meta_title'] ?? [], 'default'),
                    config('app.name'),
                    'Ticket',
                ]),
                'defaultDescription' => $this->firstString([
                    data_get($seoDefaults, 'description'),
                    data_get($seo['meta_description'] ?? [], 'default'),
                    'Portail public unifié pour billetterie, réservations, paiements et contenus multi-modules.',
                ]),
                'keywords' => $this->stringArray(data_get($seoDefaults, 'keywords')) ?: array_values(array_filter(array_map(
                    fn ($keyword): string => trim((string) $keyword),
                    explode(',', (string) data_get($seo['meta_keywords'] ?? [], 'default', '')),
                ))),
                'canonicalUrl' => $this->nullableString(data_get($seoDefaults, 'canonical_url')),
                'robots' => [
                    'index' => $this->firstString([data_get($seoDefaults, 'robots_index'), data_get($seoRobots, 'robots_index'), 'index']),
                    'follow' => $this->firstString([data_get($seoDefaults, 'robots_follow'), data_get($seoRobots, 'robots_follow'), 'follow']),
                    'maxImagePreview' => $this->firstString([data_get($seoDefaults, 'max_image_preview'), data_get($seoRobots, 'max_image_preview'), 'large']),
                    'allowPaths' => $this->stringArray(data_get($seoRobots, 'allow_paths')),
                    'disallowPaths' => $this->stringArray(data_get($seoRobots, 'disallow_paths')),
                ],
                'openGraph' => [
                    'title' => $this->nullableString(data_get($seoOpenGraph, 'og_title')),
                    'description' => $this->nullableString(data_get($seoOpenGraph, 'og_description')),
                    'type' => $this->firstString([data_get($seoOpenGraph, 'og_type'), 'website']),
                    'imageUrl' => $this->publicAssetUrl(data_get($seoOpenGraph, 'og_image_url')),
                    'imageAlt' => $this->nullableString(data_get($seoOpenGraph, 'og_image_alt')),
                ],
                'twitter' => [
                    'card' => $this->firstString([data_get($seoTwitter, 'twitter_card'), 'summary_large_image']),
                    'title' => $this->nullableString(data_get($seoTwitter, 'twitter_title')),
                    'description' => $this->nullableString(data_get($seoTwitter, 'twitter_description')),
                    'imageUrl' => $this->publicAssetUrl(data_get($seoTwitter, 'twitter_image_url')),
                    'site' => $this->nullableString(data_get($seoTwitter, 'twitter_site')),
                ],
                'structuredDataJson' => $this->nullableString(data_get($seoStructuredData, 'structured_data_json')),
                'sitemap' => [
                    'enabled' => (bool) data_get($seoSitemap, 'include_in_sitemap', true),
                    'includeFrontPages' => (bool) data_get($seoSitemap, 'include_front_pages', true),
                    'includeCatalog' => (bool) data_get($seoSitemap, 'include_catalog', true),
                    'includeOrganizers' => (bool) data_get($seoSitemap, 'include_organizers', true),
                ],
            ],
            'menus' => array_replace(self::DEFAULT_MENUS, $menus),
            'featureFlags' => array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', $featureFlags))),
            'usersCount' => $usersCount,
            'defaultTenant' => $defaultTenant,
            'languages' => $languages,
            'defaultLanguage' => collect($languages)->firstWhere('is_default', true) ?? ($languages[0] ?? null),
            'translations' => $translations,
        ];
    }

    private function groupValue(array $group, string $key, array $containers): mixed
    {
        if (array_key_exists($key, $group)) {
            return $group[$key];
        }

        foreach ($containers as $container) {
            $value = $group[$container] ?? null;

            if (is_array($value) && array_key_exists($key, $value)) {
                return $value[$key];
            }
        }

        return null;
    }

    private function firstString(array $values): string
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

    private function publicAssetUrl(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        $normalized = $this->nullableString($value);

        if ($normalized === null || Str::startsWith($normalized, ['http://', 'https://', 'data:', '/'])) {
            return $normalized;
        }

        return Storage::disk('public')->url($normalized);
    }

    private function stringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($entry) => is_string($entry) ? trim($entry) : '',
            $value,
        )));
    }

    private function firstArray(array $values): array
    {
        foreach ($values as $value) {
            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        return [];
    }

    private function publicLanguages(): array
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'locale', 'name', 'native_name', 'meta'])
            ->map(fn (Language $language): array => [
                'code' => (string) $language->code,
                'locale' => (string) ($language->locale ?: $language->code),
                'name' => (string) $language->name,
                'native_name' => (string) ($language->native_name ?: $language->name),
                'is_default' => (bool) data_get($language->meta ?? [], 'is_default', false),
            ])
            ->values()
            ->all();
    }

    private function publicTranslations(): array
    {
        return TranslationEntry::query()
            ->with('language:id,code,locale')
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy(fn (TranslationEntry $entry): string => (string) ($entry->language?->code ?: ''))
            ->filter(fn ($entries, string $locale): bool => $locale !== '')
            ->map(fn ($entries): array => $entries
                ->mapWithKeys(fn (TranslationEntry $entry): array => [
                    trim((string) $entry->key) => (string) $entry->value,
                ])
                ->filter(fn (string $value, string $key): bool => $key !== '' && $value !== '')
                ->all())
            ->all();
    }

    private function socialLinks(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($entry): ?array {
            if (! is_array($entry)) {
                return null;
            }

            $label = $this->firstString([
                $entry['label'] ?? null,
                $entry['platform'] ?? null,
                $entry['url'] ?? null,
            ]);
            $url = trim((string) ($entry['url'] ?? ''));

            if ($label === '' || $url === '') {
                return null;
            }

            return [
                'label' => $label,
                'url' => $url,
            ];
        }, $value)));
    }
}
