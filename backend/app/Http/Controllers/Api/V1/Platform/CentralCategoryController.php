<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Enums\CategoryScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\StoreCentralCategoryRequest;
use App\Models\CentralCategory;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;

class CentralCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = CentralCategory::query()
            ->with(['parent', 'children'])
            ->orderBy('module_scope')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function store(StoreCentralCategoryRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $category = CentralCategory::query()->create([
            'public_id' => (string) Str::uuid(),
            'parent_id' => $payload['parent_id'] ?? null,
            'name' => $payload['name'],
            'slug' => $payload['slug'] ?? Str::slug($payload['name']),
            'description' => $payload['description'] ?? null,
            'module_scope' => $payload['module_scope'] ?? CategoryScope::Global,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'meta' => $payload['meta'] ?? [],
        ]);

        return response()->json([
            'data' => $category->load(['parent', 'children']),
        ], 201);
    }

    public function show(CentralCategory $category): JsonResponse
    {
        return response()->json([
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    public function syncTenant(Tenant $tenant, TenantReferenceCatalog $tenantReferenceCatalog): JsonResponse
    {
        return response()->json([
            'data' => $tenantReferenceCatalog->syncCategories($tenant),
        ]);
    }
}
