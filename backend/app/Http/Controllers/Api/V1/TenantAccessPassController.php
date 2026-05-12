<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tenancy\AccessPassService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAccessPassController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassService $accessPassService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassService->listForBuyer(
                $user,
                $request->query('status'),
                $request->query('type'),
            ),
        ]);
    }

    public function show(Request $request, string $tenant, string $accessPass): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $record = $this->accessPassService->findByIdentifierForBuyer($user, $accessPass);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
