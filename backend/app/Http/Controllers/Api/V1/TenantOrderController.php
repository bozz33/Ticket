<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\OrderCatalog;

class TenantOrderController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrderCatalog $orderCatalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->orderCatalog->listForBuyer(
                $user,
                $request->query('status'),
                min(100, max(1, (int) $request->query('limit', 20))),
            ),
        ]);
    }

    public function show(Request $request, string $tenant, string $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $record = $this->orderCatalog->findByIdentifierForBuyer($user, $order);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
