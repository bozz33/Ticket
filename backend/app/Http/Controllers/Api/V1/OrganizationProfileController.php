<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertOrganizationProfileRequest;
use App\Services\Tenancy\TenantPublicProfileService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class OrganizationProfileController extends Controller
{
    public function show(TenantContext $tenantContext, TenantPublicProfileService $tenantPublicProfileService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantPublicProfileService->getOrCreate()->load(['contacts', 'socialLinks']),
        ]);
    }

    public function showPublic(TenantContext $tenantContext, TenantPublicProfileService $tenantPublicProfileService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['public_id', 'name', 'slug']),
            'data' => $tenantPublicProfileService->getPublicProjection(),
        ]);
    }

    public function upsert(
        UpsertOrganizationProfileRequest $request,
        TenantContext $tenantContext,
        TenantPublicProfileService $tenantPublicProfileService,
    ): JsonResponse {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantPublicProfileService->update($request->validated()),
        ]);
    }
}
