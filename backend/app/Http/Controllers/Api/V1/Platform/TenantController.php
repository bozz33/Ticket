<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\Tenancy\ManageTenantLifecycle;
use App\Services\Tenancy\ProvisionTenant;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::query()
            ->with(['profile', 'domains'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $tenants,
        ]);
    }

    public function store(StoreTenantRequest $request, ProvisionTenant $provisionTenant): JsonResponse
    {
        $result = $provisionTenant->handle($request->validated());

        return response()->json([
            'data' => $result['tenant'],
            'tenant_admin' => $result['tenant_admin'],
        ], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => $tenant->load(['profile', 'domains', 'statusHistories']),
        ]);
    }

    public function activate(Tenant $tenant, ManageTenantLifecycle $manageTenantLifecycle): JsonResponse
    {
        return response()->json([
            'data' => $manageTenantLifecycle->activate($tenant),
        ]);
    }

    public function suspend(Tenant $tenant, ManageTenantLifecycle $manageTenantLifecycle): JsonResponse
    {
        return response()->json([
            'data' => $manageTenantLifecycle->suspend($tenant),
        ]);
    }

    public function archive(Tenant $tenant, ManageTenantLifecycle $manageTenantLifecycle): JsonResponse
    {
        return response()->json([
            'data' => $manageTenantLifecycle->archive($tenant),
        ]);
    }
}
