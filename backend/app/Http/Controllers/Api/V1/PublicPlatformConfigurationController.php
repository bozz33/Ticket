<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use App\Services\FeatureFlagService;
use App\Services\FrontCmsService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicPlatformConfigurationController extends Controller
{
    private const TTL = 300;
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
        FrontCmsService $frontCmsService,
    ): JsonResponse {
        return $this->__invoke($platformSettingsService, $featureFlagService, $frontCmsService);
    }

    public function __invoke(
        PlatformSettingsService $platformSettingsService,
        FeatureFlagService $featureFlagService,
        FrontCmsService $frontCmsService,
    ): JsonResponse {
        $payload = Cache::remember('public_platform_configuration', now()->addSeconds(self::TTL), function () use ($platformSettingsService, $featureFlagService, $frontCmsService): array {
            $settings = $platformSettingsService->grouped(publicOnly: true);
            $menus = array_replace($frontCmsService->defaultMenus(), $frontCmsService->publicMenus());
            $featureFlags = $featureFlagService->publicFlags();
            $publicUsersCount = (int) PlatformTransaction::query()
                ->whereIn('type', ['public_checkout', 'gateway_charge'])
                ->whereIn('status', ['success', 'successful', 'confirmed', 'completed', 'paid'])
                ->whereRaw("COALESCE(meta->'checkout'->>'buyer_email', meta->'gateway_payload'->'data'->'metadata'->>'buyer_email', meta->'data'->'metadata'->>'buyer_email', '') <> ''")
                ->selectRaw("COUNT(DISTINCT COALESCE(meta->'checkout'->>'buyer_email', meta->'gateway_payload'->'data'->'metadata'->>'buyer_email', meta->'data'->'metadata'->>'buyer_email')) as aggregate")
                ->value('aggregate');

            $defaultTenant = Tenant::query()
                ->where('status', TenantStatus::Active->value)
                ->orderByDesc('activated_at')
                ->orderBy('id')
                ->first(['public_id', 'name', 'slug']);

            $normalized = $this->normalizedConfiguration($settings, $menus, $featureFlags->pluck('code')->values()->all(), $publicUsersCount, $defaultTenant?->only(['public_id', 'name', 'slug']));

            return [
                'data' => $normalized,
                'settings' => $settings,
                'menus' => $menus,
                'feature_flags' => $featureFlags,
                'stats' => [
                    'users_count' => $publicUsersCount,
                ],
                'default_tenant' => $defaultTenant?->only(['public_id', 'name', 'slug']),
            ];
        });

        return response()->json($payload, 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::TTL, self::TTL),
        ]);
    }

    private function normalizedConfiguration(array $settings, array $menus, array $featureFlags, int $usersCount, ?array $defaultTenant): array
    {
        $branding = $settings['branding'] ?? [];
        $support = $settings['support'] ?? [];
        $payments = $settings['payments'] ?? [];
        $navigation = $settings['navigation'] ?? [];
        $footer = $settings['footer'] ?? [];

        return [
            'brandName' => $this->firstString([
                $this->groupValue($branding, 'platform_name', ['site_identity', 'brand', 'public_branding']),
                config('app.name'),
                'Ticket',
            ]),
            'logoUrl' => $this->nullableString($this->groupValue($branding, 'logo_url', ['site_identity', 'brand', 'public_branding'])),
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
            'socialLinks' => $this->socialLinks($this->groupValue($footer, 'social_links', ['public_footer', 'footer_content'])),
            'menus' => array_replace(self::DEFAULT_MENUS, $menus),
            'featureFlags' => array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', $featureFlags))),
            'usersCount' => $usersCount,
            'defaultTenant' => $defaultTenant,
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
