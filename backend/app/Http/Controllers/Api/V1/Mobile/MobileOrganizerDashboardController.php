<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\AccessPassStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizationProfile;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOrganizerDashboardController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        abort_unless($this->canAccessOrganizerArea($user), 403);

        $profile = OrganizationProfile::query()->with(['contacts', 'socialLinks'])->first();

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'organization_profile' => $profile ? [
                    'id' => $profile->id,
                    'display_name' => $profile->display_name,
                    'legal_name' => $profile->legal_name,
                    'email' => $profile->email,
                    'phone' => $profile->phone,
                    'website_url' => $profile->website_url,
                    'logo_url' => $profile->logo_url,
                    'banner_url' => $profile->banner_url,
                ] : null,
                'summary' => [
                    'upcoming_events_count' => Event::query()->where('is_active', true)->count(),
                    'confirmed_orders_count' => Order::query()->where('status', OrderStatus::Confirmed->value)->count(),
                    'active_passes_count' => AccessPass::query()->where('status', AccessPassStatus::Active->value)->count(),
                    'used_passes_count' => AccessPass::query()->where('status', AccessPassStatus::Used->value)->count(),
                    'today_checkins_count' => AccessPassScan::query()->whereDate('scanned_at', now()->toDateString())->count(),
                    'unread_notifications_count' => $user->unreadNotifications()->count(),
                ],
                'upcoming_events' => Event::query()
                    ->with('dates')
                    ->where('is_active', true)
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Event $event): array => [
                        'id' => $event->id,
                        'public_id' => $event->public_id,
                        'title' => $event->title,
                        'slug' => $event->slug,
                        'public_status_code' => $event->public_status_code,
                        'cover_image_url' => $event->cover_image_url,
                        'venue_name' => $event->venue_name,
                        'city_id' => $event->city_id,
                        'published_at' => $event->published_at?->toISOString(),
                        'next_date' => $event->dates->first()?->starts_at?->toISOString(),
                    ])
                    ->values(),
                'recent_orders' => Order::query()
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Order $order): array => [
                        'id' => $order->id,
                        'public_id' => $order->public_id,
                        'reference' => $order->reference,
                        'status' => $order->status?->value,
                        'buyer_name' => $order->buyer_name,
                        'total_amount' => $order->total_amount,
                        'currency_code' => $order->currency_code,
                        'created_at' => $order->created_at?->toISOString(),
                    ])
                    ->values(),
                'recent_checkins' => AccessPassScan::query()
                    ->with('accessPass')
                    ->latest('scanned_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (AccessPassScan $scan): array => [
                        'id' => $scan->id,
                        'access_pass_id' => $scan->access_pass_id,
                        'access_code' => $scan->accessPass?->access_code,
                        'action' => $scan->action,
                        'result' => $scan->result?->value,
                        'scanned_at' => $scan->scanned_at?->toISOString(),
                    ])
                    ->values(),
            ],
        ]);
    }

    private function canAccessOrganizerArea(User $user): bool
    {
        return $user->hasRole('owner') || $user->can('tenant.access');
    }
}
