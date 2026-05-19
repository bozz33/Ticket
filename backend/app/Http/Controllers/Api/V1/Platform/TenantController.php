<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\StoreTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Ticket\Tenancy\Contracts\TenantLifecycleManager;
use Ticket\Tenancy\Contracts\TenantProvisioner;

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

    public function store(StoreTenantRequest $request, TenantProvisioner $tenantProvisioner): JsonResponse
    {
        $result = $tenantProvisioner->handle($request->validated());

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

    public function activate(Tenant $tenant, TenantLifecycleManager $tenantLifecycleManager): JsonResponse
    {
        return response()->json([
            'data' => $tenantLifecycleManager->activate($tenant),
        ]);
    }

    public function suspend(Tenant $tenant, TenantLifecycleManager $tenantLifecycleManager): JsonResponse
    {
        return response()->json([
            'data' => $tenantLifecycleManager->suspend($tenant),
        ]);
    }

    public function archive(Tenant $tenant, TenantLifecycleManager $tenantLifecycleManager): JsonResponse
    {
        return response()->json([
            'data' => $tenantLifecycleManager->archive($tenant),
        ]);
    }
}
