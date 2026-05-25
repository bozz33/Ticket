<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentLike;
use App\Models\Event;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ticket\PublicCatalog\Application\PublicCatalogEngagementUpdater;
use Ticket\Ticketing\Contracts\EventEngagement;

class TenantEventLikeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EventEngagement $eventEngagement,
        private readonly PublicCatalogEngagementUpdater $catalogEngagementUpdater,
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
        $data = $this->eventEngagement->like($user, $record);
        $this->syncProjection($record, (int) ($data['likes'] ?? 0));

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $data,
            'message' => 'Événement ajouté à vos favoris.',
        ]);
    }

    public function destroy(Request $request, string $tenant, string $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findEvent($event);

        abort_if($record === null, 404);
        $data = $this->eventEngagement->unlike($user, $record);
        $this->syncProjection($record, (int) ($data['likes'] ?? 0));

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $data,
            'message' => 'Événement retiré de vos favoris.',
        ]);
    }

    private function syncProjection(Event $event, int $likesCount): void
    {
        $contentUserIds = ContentLike::query()
            ->where('module', 'evenements')
            ->where('content_slug', $event->slug)
            ->where('created_at', '>=', CarbonImmutable::now()->startOfWeek())
            ->pluck('user_id')
            ->all();
        $legacyUserIds = $event->likes()
            ->where('created_at', '>=', CarbonImmutable::now()->startOfWeek())
            ->pluck('user_id')
            ->all();

        $this->catalogEngagementUpdater->syncContentLikeCounts(
            $this->tenantContext->get(),
            'evenements',
            (string) $event->slug,
            $likesCount,
            collect($contentUserIds)->merge($legacyUserIds)->unique()->count(),
        );
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
