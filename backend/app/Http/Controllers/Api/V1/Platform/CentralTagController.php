<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Enums\CategoryScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\StoreCentralTagRequest;
use App\Models\CentralTag;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;

class CentralTagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = CentralTag::query()
            ->orderBy('module_scope')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $tags,
        ]);
    }

    public function store(StoreCentralTagRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $tag = CentralTag::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $payload['name'],
            'slug' => $payload['slug'] ?? Str::slug($payload['name']),
            'description' => $payload['description'] ?? null,
            'module_scope' => $payload['module_scope'] ?? CategoryScope::Global,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'meta' => $payload['meta'] ?? [],
        ]);

        return response()->json([
            'data' => $tag,
        ], 201);
    }

    public function show(CentralTag $tag): JsonResponse
    {
        return response()->json([
            'data' => $tag,
        ]);
    }

    public function syncTenant(Tenant $tenant, TenantReferenceCatalog $tenantReferenceCatalog): JsonResponse
    {
        return response()->json([
            'data' => $tenantReferenceCatalog->syncTags($tenant),
        ]);
    }
}
