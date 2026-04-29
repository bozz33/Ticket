<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\OrderService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantOrderController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->orderService->list($request->query('status')),
        ]);
    }

    public function show(string $order): JsonResponse
    {
        $record = $this->orderService->findByIdentifier($order);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
