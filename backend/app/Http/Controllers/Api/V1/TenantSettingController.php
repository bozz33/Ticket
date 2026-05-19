<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertTenantSettingsRequest;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Tenancy\Contracts\TenantSettingsManager;

class TenantSettingController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, TenantSettingsManager $tenantSettingsManager): JsonResponse
    {
        $group = $request->query('group');

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantSettingsManager->list($group),
        ]);
    }

    public function upsert(UpsertTenantSettingsRequest $request, TenantContext $tenantContext, TenantSettingsManager $tenantSettingsManager): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tenantSettingsManager->upsertMany($request->validated('items')),
        ]);
    }
}
