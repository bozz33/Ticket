<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\AccessPassService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class PublicAccessPassController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassService $accessPassService,
    ) {}

    public function show(string $tenant, string $code): JsonResponse
    {
        $pass = $this->accessPassService->findByCode($code);

        if ($pass === null) {
            return response()->json(['message' => 'Pass introuvable.'], 404);
        }

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'public_id' => $pass->public_id,
                'type' => $pass->type->value,
                'type_label' => $pass->type->label(),
                'status' => $pass->status->value,
                'holder_name' => $pass->holder_name,
                'used_at' => $pass->used_at?->toIso8601String(),
                'expires_at' => $pass->expires_at?->toIso8601String(),
                'qr_payload' => $pass->toQrPayload(),
            ],
        ]);
    }
}
