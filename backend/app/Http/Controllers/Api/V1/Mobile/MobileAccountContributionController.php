<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CrowdfundingContribution;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAccountContributionController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $contributions = CrowdfundingContribution::query()
            ->with(['campaign', 'order'])
            ->where(function ($query) use ($user): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhere('contributor_email', $user->email);
            })
            ->latest('id')
            ->limit(max(1, min(100, (int) $request->query('limit', 50))))
            ->get();

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $contributions->map(fn (CrowdfundingContribution $contribution): array => [
                'id' => $contribution->id,
                'public_id' => $contribution->public_id,
                'status' => $contribution->status,
                'amount' => $contribution->amount,
                'refunded_amount' => $contribution->refunded_amount,
                'currency_code' => $contribution->currency_code,
                'is_anonymous' => $contribution->is_anonymous,
                'paid_at' => $contribution->paid_at?->toISOString(),
                'refunded_at' => $contribution->refunded_at?->toISOString(),
                'campaign' => $contribution->campaign ? [
                    'id' => $contribution->campaign->id,
                    'public_id' => $contribution->campaign->public_id,
                    'title' => $contribution->campaign->title,
                    'slug' => $contribution->campaign->slug,
                    'public_status_code' => $contribution->campaign->public_status_code,
                ] : null,
                'order' => $contribution->order ? [
                    'id' => $contribution->order->id,
                    'public_id' => $contribution->order->public_id,
                    'reference' => $contribution->order->reference,
                    'status' => $contribution->order->status?->value,
                ] : null,
            ])->values(),
        ]);
    }
}
