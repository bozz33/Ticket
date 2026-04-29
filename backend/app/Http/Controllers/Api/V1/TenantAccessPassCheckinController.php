<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\AccessPassCheckinService;
use App\Services\Tenancy\AccessPassService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAccessPassCheckinController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassService $accessPassService,
        private readonly AccessPassCheckinService $checkinService,
    ) {}

    public function preview(Request $request, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->checkinService->preview($pass, $request),
        ]);
    }

    public function consume(Request $request, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->checkinService->consume($pass, $request),
        ]);
    }

    public function reset(Request $request, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->checkinService->reset($pass, $request),
        ]);
    }

    public function revoke(Request $request, string $accessPass): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['sometimes', 'string', 'max:255'],
        ]);

        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->checkinService->revoke($pass, $request, $validated['reason'] ?? ''),
        ]);
    }

    public function reactivate(Request $request, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->checkinService->reactivate($pass, $request),
        ]);
    }

    private function resolvePass(string $identifier): \App\Models\AccessPass
    {
        $pass = $this->accessPassService->findByIdentifier($identifier);

        abort_if($pass === null, 404, 'Pass introuvable.');

        return $pass;
    }
}
