<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RevokeAccessPassRequest;
use App\Models\AccessPass;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\AccessPassCatalog;
use Ticket\Ticketing\Contracts\AccessPassCheckin;

class TenantAccessPassCheckinController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassCatalog $accessPassCatalog,
        private readonly AccessPassCheckin $accessPassCheckin,
    ) {}

    public function preview(Request $request, string $tenant, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCheckin->preview($pass, $request),
        ]);
    }

    public function consume(Request $request, string $tenant, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCheckin->consume($pass, $request),
        ]);
    }

    public function reset(Request $request, string $tenant, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCheckin->reset($pass, $request),
        ]);
    }

    public function revoke(RevokeAccessPassRequest $request, string $tenant, string $accessPass): JsonResponse
    {
        $validated = $request->validated();

        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCheckin->revoke($pass, $request, $validated['reason'] ?? ''),
        ]);
    }

    public function reactivate(Request $request, string $tenant, string $accessPass): JsonResponse
    {
        $pass = $this->resolvePass($accessPass);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCheckin->reactivate($pass, $request),
        ]);
    }

    private function resolvePass(string $identifier): AccessPass
    {
        $pass = $this->accessPassCatalog->findByIdentifier($identifier);

        abort_if($pass === null, 404, 'Pass introuvable.');

        return $pass;
    }
}
