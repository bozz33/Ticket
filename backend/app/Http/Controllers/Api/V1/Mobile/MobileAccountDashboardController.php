<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\AccessPassStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\CallForProjectSubmission;
use App\Models\CrowdfundingContribution;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAccountDashboardController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'user' => $this->serializeUser($user),
                'summary' => [
                    'orders_count' => Order::query()->where('buyer_user_id', $user->getKey())->count(),
                    'active_passes_count' => AccessPass::query()
                        ->where('holder_user_id', $user->getKey())
                        ->where('status', AccessPassStatus::Active->value)
                        ->count(),
                    'receipts_count' => Receipt::query()->where('buyer_user_id', $user->getKey())->count(),
                    'applications_count' => CallForProjectSubmission::query()->where('applicant_email', $user->email)->count(),
                    'contributions_count' => CrowdfundingContribution::query()
                        ->where(function ($query) use ($user): void {
                            $query->where('buyer_user_id', $user->getKey())
                                ->orWhere('contributor_email', $user->email);
                        })
                        ->count(),
                    'unread_notifications_count' => $user->unreadNotifications()->count(),
                ],
                'recent_orders' => Order::query()
                    ->where('buyer_user_id', $user->getKey())
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Order $order): array => $this->serializeOrder($order))
                    ->values(),
                'active_passes' => AccessPass::query()
                    ->where('holder_user_id', $user->getKey())
                    ->where('status', AccessPassStatus::Active->value)
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (AccessPass $pass): array => $this->serializeAccessPass($pass))
                    ->values(),
                'recent_receipts' => Receipt::query()
                    ->where('buyer_user_id', $user->getKey())
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Receipt $receipt): array => $this->serializeReceipt($receipt))
                    ->values(),
                'notifications' => $user->notifications()
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn ($notification): array => [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at?->toISOString(),
                        'created_at' => $notification->created_at?->toISOString(),
                    ])
                    ->values(),
            ],
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }

    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'public_id' => $order->public_id,
            'reference' => $order->reference,
            'status' => $order->status?->value,
            'quantity' => $order->quantity,
            'total_amount' => $order->total_amount,
            'currency_code' => $order->currency_code,
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    private function serializeAccessPass(AccessPass $pass): array
    {
        return [
            'id' => $pass->id,
            'public_id' => $pass->public_id,
            'access_code' => $pass->access_code,
            'type' => $pass->type?->value,
            'status' => $pass->status?->value,
            'holder_name' => $pass->holder_name,
            'expires_at' => $pass->expires_at?->toISOString(),
            'qr_payload' => $pass->toQrPayload(),
        ];
    }

    private function serializeReceipt(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'public_id' => $receipt->public_id,
            'reference' => $receipt->reference,
            'status' => $receipt->status,
            'total_amount' => $receipt->total_amount,
            'currency_code' => $receipt->currency_code,
            'issued_at' => $receipt->issued_at?->toISOString(),
        ];
    }
}
