<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\AccessPassCatalog;

class TenantAccessPassController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassCatalog $accessPassCatalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->accessPassCatalog->listForBuyer(
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

        $record = $this->accessPassCatalog->findByIdentifierForBuyer($user, $accessPass);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
