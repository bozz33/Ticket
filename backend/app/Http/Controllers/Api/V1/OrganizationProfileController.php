<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertOrganizationProfileRequest;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\GlobalCache;
use Ticket\PublicCatalog\Contracts\PublicContentCatalog;
use Ticket\Tenancy\Contracts\TenantProfileManager;

class OrganizationProfileController extends Controller
{
    private const PUBLIC_PROFILE_TTL = 120;

    private const PUBLIC_CATALOG_TTL = 120;

    public function show(TenantContext $tenantContext, TenantProfileManager $tenantProfileManager): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantProfileManager->getOrCreate()->load(['contacts', 'socialLinks']),
        ]);
    }

    public function showPublic(TenantContext $tenantContext, TenantProfileManager $tenantProfileManager): JsonResponse
    {
        $tenant = $tenantContext->get()?->only(['public_id', 'name', 'slug']);
        $tenantSlug = is_array($tenant) ? ($tenant['slug'] ?? '') : '';
        $payload = GlobalCache::remember(
            sprintf('public_organization_profile:%s', md5((string) $tenantSlug)),
            now()->addSeconds(self::PUBLIC_PROFILE_TTL),
            fn (): array => [
                'tenant' => $tenant,
                'data' => $tenantProfileManager->getPublicViewData($tenant),
            ],
        );

        return response()->json($payload, 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::PUBLIC_PROFILE_TTL, self::PUBLIC_PROFILE_TTL),
        ]);
    }

    public function showPublicCatalog(
        Request $request,
        TenantContext $tenantContext,
        TenantProfileManager $tenantProfileManager,
        PublicContentCatalog $publicContentCatalog,
    ): JsonResponse {
        $filters = $request->only(['module', 'q', 'category', 'city', 'date_from', 'date_to', 'price', 'sort', 'featured']);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(48, max(1, (int) $request->query('per_page', 12)));
        $tenant = $tenantContext->get()?->only(['public_id', 'name', 'slug']);
        $tenantSlug = is_array($tenant) ? ($tenant['slug'] ?? '') : '';
        $payload = GlobalCache::remember(
            sprintf(
                'public_organization_catalog:%s:%s',
                md5((string) $tenantSlug),
                md5(json_encode([
                    'filters' => $filters,
                    'page' => $page,
                    'per_page' => $perPage,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ),
            now()->addSeconds(self::PUBLIC_CATALOG_TTL),
            function () use ($tenant, $tenantProfileManager, $publicContentCatalog, $filters, $page, $perPage): array {
                $result = $publicContentCatalog->list($filters, $page, $perPage);
                $available = $publicContentCatalog->availableFilters($filters['module'] ?? null);

                return [
                    'tenant' => $tenant,
                    'data' => [
                        'profile' => $tenantProfileManager->getPublicViewData($tenant, $result['items']),
                        'items' => $result['items'],
                        'stats' => $publicContentCatalog->summaryStats(),
                    ],
                    'meta' => [
                        'current_page' => $result['currentPage'],
                        'total' => $result['total'],
                        'total_pages' => $result['totalPages'],
                        'per_page' => $perPage,
                    ],
                    'filters' => $available,
                ];
            },
        );

        return response()->json($payload, 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::PUBLIC_CATALOG_TTL, self::PUBLIC_CATALOG_TTL),
        ]);
    }

    public function upsert(
        UpsertOrganizationProfileRequest $request,
        TenantContext $tenantContext,
        TenantProfileManager $tenantProfileManager,
    ): JsonResponse {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantProfileManager->update($request->validated()),
        ]);
    }
}
