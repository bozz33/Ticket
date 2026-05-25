<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\OrganizationAudience;

class TenantOrganizationFollowController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrganizationAudience $organizationAudience,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->organizationAudience->status($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'message' => 'Vous suivez désormais cette organisation.',
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->organizationAudience->follow($user),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'message' => 'Vous ne suivez plus cette organisation.',
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->organizationAudience->unfollow($user),
        ]);
    }
}
