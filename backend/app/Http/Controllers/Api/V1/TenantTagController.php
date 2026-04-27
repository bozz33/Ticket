<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantTagController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenantContext): JsonResponse
    {
        $scope = $request->query('scope');

        $tags = Tag::query()
            ->when($scope !== null && $scope !== '', fn ($query) => $query->where('module_scope', $scope))
            ->orderBy('module_scope')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tags,
        ]);
    }
}
