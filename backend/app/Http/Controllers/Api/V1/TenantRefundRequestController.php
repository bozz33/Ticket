<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTenantRefundRequest;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Ticket\Ticketing\Contracts\BuyerRefundRequests;

class TenantRefundRequestController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly BuyerRefundRequests $buyerRefundRequests,
    ) {}

    public function store(StoreTenantRefundRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $payload = $request->validated();

        try {
            $order = $this->buyerRefundRequests->request($user, (string) $payload['order_reference'], $payload);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'message' => 'Votre demande de remboursement a été enregistrée.',
            'data' => $order,
        ], 201);
    }
}
