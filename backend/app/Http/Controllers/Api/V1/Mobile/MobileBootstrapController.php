<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\AccessPassStatus;
use App\Enums\OrderStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class MobileBootstrapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $defaultTenant = Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->latest('id')
            ->first();

        return response()->json([
            'data' => [
                'api_version' => 'v1',
                'mobile_api_version' => 'mvp-1',
                'default_tenant' => $defaultTenant?->only(['id', 'public_id', 'name', 'slug']),
                'capabilities' => [
                    'public_catalog' => true,
                    'account_dashboard' => true,
                    'organizer_dashboard' => true,
                    'organizer_participants' => true,
                    'organizer_stats' => true,
                    'checkout_direct' => true,
                    'ticket_reservations' => true,
                    'checkin_online' => true,
                    'crowdfunding_guest_checkout' => true,
                    'global_cart' => false,
                    'offline_checkin' => false,
                    'mobile_content_creation' => false,
                    'push_devices' => true,
                    'support_mobile' => true,
                    'account_applications' => true,
                    'account_contributions' => true,
                    'monitoring_mobile' => false,
                ],
                'enums' => [
                    'order_statuses' => OrderStatus::options(),
                    'access_pass_statuses' => AccessPassStatus::options(),
                ],
            ],
        ]);
    }
}
