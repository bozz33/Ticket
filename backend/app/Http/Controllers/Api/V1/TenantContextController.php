<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class TenantContextController extends Controller
{
    public function __invoke(TenantContext $tenantContext): JsonResponse
    {
        $tenant = $tenantContext->get();

        return response()->json([
            'tenant' => [
                'id' => $tenant?->id,
                'public_id' => $tenant?->public_id,
                'name' => $tenant?->name,
                'slug' => $tenant?->slug,
                'status' => $tenant?->status?->value,
                'locale' => $tenant?->locale,
                'timezone' => $tenant?->timezone,
            ],
        ]);
    }
}
