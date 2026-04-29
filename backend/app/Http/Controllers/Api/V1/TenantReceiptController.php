<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\ReceiptService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantReceiptController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReceiptService $receiptService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->receiptService->list($request->query('status')),
        ]);
    }

    public function show(string $receipt): JsonResponse
    {
        $record = $this->receiptService->findByIdentifier($receipt);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
