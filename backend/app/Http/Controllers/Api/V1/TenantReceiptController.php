<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\ReceiptCatalog;

class TenantReceiptController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReceiptCatalog $receiptCatalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->receiptCatalog->listForBuyer($user, $request->query('status')),
        ]);
    }

    public function show(Request $request, string $tenant, string $receipt): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $record = $this->receiptCatalog->findByIdentifierForBuyer($user, $receipt);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
