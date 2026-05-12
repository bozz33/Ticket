<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Buyers\BuyerAccountReadiness;
use App\Services\Tenancy\OrganizationFollowService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantOrganizationFollowController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrganizationFollowService $followService,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->followService->status($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        try {
            $this->buyerAccountReadiness->assertReadyForSensitiveAction($user, 'suivre cette organisation');

            return response()->json([
                'message' => 'Vous suivez désormais cette organisation.',
                'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
                'data' => $this->followService->follow($user),
            ]);
        } catch (BuyerAccountActionBlockedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
                'requirements' => $exception->requirements(),
            ], 422);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        try {
            $this->buyerAccountReadiness->assertReadyForSensitiveAction($user, 'gérer cet abonnement');

            return response()->json([
                'message' => 'Vous ne suivez plus cette organisation.',
                'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
                'data' => $this->followService->unfollow($user),
            ]);
        } catch (BuyerAccountActionBlockedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
                'requirements' => $exception->requirements(),
            ], 422);
        }
    }
}
