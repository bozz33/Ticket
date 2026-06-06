<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\AccessPassStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use App\Models\Order;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOrganizerStatsController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        abort_unless($user->hasRole('owner') || $user->can('tenant.access'), 403);

        $confirmedOrders = Order::query()->where('status', OrderStatus::Confirmed->value);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'sales' => [
                    'today_amount' => (clone $confirmedOrders)->whereDate('created_at', now()->toDateString())->sum('total_amount'),
                    'month_amount' => (clone $confirmedOrders)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
                    'total_amount' => (clone $confirmedOrders)->sum('total_amount'),
                    'currency_code' => Order::query()->latest('id')->value('currency_code'),
                ],
                'orders' => [
                    'today_count' => Order::query()->whereDate('created_at', now()->toDateString())->count(),
                    'month_count' => Order::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                    'confirmed_count' => Order::query()->where('status', OrderStatus::Confirmed->value)->count(),
                    'pending_count' => Order::query()->where('status', OrderStatus::Pending->value)->count(),
                    'refund_pending_count' => Order::query()->where('status', OrderStatus::RefundPending->value)->count(),
                ],
                'passes' => [
                    'active_count' => AccessPass::query()->where('status', AccessPassStatus::Active->value)->count(),
                    'used_count' => AccessPass::query()->where('status', AccessPassStatus::Used->value)->count(),
                    'revoked_count' => AccessPass::query()->where('status', AccessPassStatus::Revoked->value)->count(),
                    'expired_count' => AccessPass::query()->where('status', AccessPassStatus::Expired->value)->count(),
                ],
                'checkins' => [
                    'today_count' => AccessPassScan::query()->whereDate('scanned_at', now()->toDateString())->count(),
                    'month_count' => AccessPassScan::query()->whereBetween('scanned_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                    'total_count' => AccessPassScan::query()->count(),
                ],
            ],
        ]);
    }
}
