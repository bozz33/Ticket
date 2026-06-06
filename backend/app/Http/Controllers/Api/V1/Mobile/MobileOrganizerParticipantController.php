<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\Event;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOrganizerParticipantController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request, string $tenant, string $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        abort_unless($user->hasRole('owner') || $user->can('tenant.access'), 403);

        $eventModel = Event::query()
            ->where('public_id', $event)
            ->orWhere('slug', $event)
            ->orWhere('id', is_numeric($event) ? (int) $event : 0)
            ->firstOrFail();

        $passes = AccessPass::query()
            ->with(['order', 'holder'])
            ->where('passable_type', $eventModel->getMorphClass())
            ->where('passable_id', $eventModel->getKey())
            ->latest('id')
            ->limit(max(1, min(100, (int) $request->query('limit', 50))))
            ->get();

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'event' => [
                'id' => $eventModel->id,
                'public_id' => $eventModel->public_id,
                'title' => $eventModel->title,
                'slug' => $eventModel->slug,
            ],
            'data' => $passes->map(fn (AccessPass $pass): array => [
                'id' => $pass->id,
                'public_id' => $pass->public_id,
                'access_code' => $pass->access_code,
                'type' => $pass->type?->value,
                'status' => $pass->status?->value,
                'holder_name' => $pass->holder_name,
                'holder_email' => $pass->holder_email,
                'used_at' => $pass->used_at?->toISOString(),
                'expires_at' => $pass->expires_at?->toISOString(),
                'order' => $pass->order ? [
                    'id' => $pass->order->id,
                    'public_id' => $pass->order->public_id,
                    'reference' => $pass->order->reference,
                    'status' => $pass->order->status?->value,
                    'total_amount' => $pass->order->total_amount,
                    'currency_code' => $pass->order->currency_code,
                ] : null,
            ])->values(),
        ]);
    }
}
