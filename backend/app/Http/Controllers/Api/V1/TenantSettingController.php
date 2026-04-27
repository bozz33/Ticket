<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertTenantSettingsRequest;
use App\Services\Tenancy\TenantSettingsService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, TenantSettingsService $tenantSettingsService): JsonResponse
    {
        $group = $request->query('group');

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantSettingsService->list($group),
        ]);
    }

    public function upsert(UpsertTenantSettingsRequest $request, TenantContext $tenantContext, TenantSettingsService $tenantSettingsService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantSettingsService->upsertMany($request->validated('items')),
        ]);
    }
}
