<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ticket\Ticketing\Contracts\EventEngagement;

class TenantEventLikeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EventEngagement $eventEngagement,
    ) {}

    public function show(Request $request, string $tenant, string $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findEvent($event);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->eventEngagement->summary($user, $record),
        ]);
    }

    public function index(Request $request, string $tenant): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('tenant_user');
        $identifiers = $request->query('events', []);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->eventEngagement->summaries(
                $user,
                is_array($identifiers) ? $identifiers : [$identifiers],
            ),
        ]);
    }

    public function store(Request $request, string $tenant, string $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findEvent($event);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->eventEngagement->like($user, $record),
            'message' => 'Événement ajouté à vos favoris.',
        ]);
    }

    public function destroy(Request $request, string $tenant, string $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findEvent($event);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->eventEngagement->unlike($user, $record),
            'message' => 'Événement retiré de vos favoris.',
        ]);
    }

    private function findEvent(string $identifier): ?Event
    {
        return Event::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (Str::isUuid($identifier)) {
                    $query->orWhere('public_id', $identifier);
                }
            })
            ->first();
    }
}
