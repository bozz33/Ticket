<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentLike;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ticket\PublicCatalog\Application\PublicCatalogEngagementUpdater;
use Ticket\PublicCatalog\Domain\PublicCatalogModules;

class TenantContentLikeController extends Controller
{
    private const MAX_BATCH_ITEMS = 100;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PublicCatalogModules $modules,
        private readonly PublicCatalogEngagementUpdater $catalogEngagementUpdater,
    ) {}

    public function index(Request $request, string $tenant): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('tenant_user');
        $pairs = $this->requestedPairs($request);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->summaries($user, $pairs),
        ]);
    }

    public function show(Request $request, string $tenant, string $module, string $content): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findContent($module, $content);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->summary($user, $module, $content),
        ]);
    }

    public function store(Request $request, string $tenant, string $module, string $content): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findContent($module, $content);

        abort_if($record === null, 404);

        ContentLike::query()->firstOrCreate([
            'module' => $module,
            'content_slug' => $content,
            'user_id' => $user->getKey(),
        ], [
            'content_public_id' => $record->public_id ?? null,
        ]);
        $summary = $this->summary($user, $module, $content);
        $this->syncProjection($module, $content, $summary['likes']);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $summary,
            'message' => 'Contenu ajouté à vos favoris.',
        ]);
    }

    public function destroy(Request $request, string $tenant, string $module, string $content): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $record = $this->findContent($module, $content);

        abort_if($record === null, 404);

        ContentLike::query()
            ->where('module', $module)
            ->where('content_slug', $content)
            ->where('user_id', $user->getKey())
            ->delete();
        $summary = $this->summary($user, $module, $content);
        $this->syncProjection($module, $content, $summary['likes']);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $summary,
            'message' => 'Contenu retiré de vos favoris.',
        ]);
    }

    private function summary(?User $user, string $module, string $content): array
    {
        $query = ContentLike::query()
            ->where('module', $module)
            ->where('content_slug', $content);

        return [
            'liked' => $user !== null && (clone $query)->where('user_id', $user->getKey())->exists(),
            'likes' => (clone $query)->count(),
        ];
    }

    /**
     * @param  array<int, array{module: string, slug: string}>  $pairs
     * @return array<string, array{liked: bool, likes: int}>
     */
    private function summaries(?User $user, array $pairs): array
    {
        if ($pairs === []) {
            return [];
        }

        $matchingContent = static function ($query) use ($pairs): void {
            $query->where(function ($nestedQuery) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $nestedQuery->orWhere(function ($contentQuery) use ($pair): void {
                        $contentQuery
                            ->where('module', $pair['module'])
                            ->where('content_slug', $pair['slug']);
                    });
                }
            });
        };

        $counts = ContentLike::query()
            ->tap($matchingContent)
            ->selectRaw('module, content_slug, count(*) as aggregate')
            ->groupBy('module', 'content_slug')
            ->get()
            ->mapWithKeys(fn (ContentLike $like): array => [
                $this->summaryKey($like->module, $like->content_slug) => (int) $like->aggregate,
            ]);

        $likedKeys = $user === null
            ? collect()
            : ContentLike::query()
                ->tap($matchingContent)
                ->where('user_id', $user->getKey())
                ->get(['module', 'content_slug'])
                ->map(fn (ContentLike $like): string => $this->summaryKey($like->module, $like->content_slug))
                ->flip();

        return collect($pairs)
            ->mapWithKeys(fn (array $pair): array => [
                $this->summaryKey($pair['module'], $pair['slug']) => [
                    'liked' => $likedKeys->has($this->summaryKey($pair['module'], $pair['slug'])),
                    'likes' => (int) ($counts[$this->summaryKey($pair['module'], $pair['slug'])] ?? 0),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array{module: string, slug: string}>
     */
    private function requestedPairs(Request $request): array
    {
        $items = $request->query('items', []);
        $items = is_array($items) ? $items : [$items];

        return collect($items)
            ->map(fn (mixed $item): string => is_string($item) ? trim($item) : '')
            ->filter(fn (string $item): bool => str_contains($item, ':'))
            ->map(function (string $item): ?array {
                [$module, $slug] = explode(':', $item, 2);
                $module = trim($module);
                $slug = trim($slug);

                if (! $this->modules->has($module) || $slug === '' || Str::length($slug) > 255) {
                    return null;
                }

                return ['module' => $module, 'slug' => $slug];
            })
            ->filter()
            ->unique(fn (array $pair): string => $this->summaryKey($pair['module'], $pair['slug']))
            ->take(self::MAX_BATCH_ITEMS)
            ->values()
            ->all();
    }

    private function summaryKey(string $module, string $slug): string
    {
        return sprintf('%s:%s', $module, $slug);
    }

    private function syncProjection(string $module, string $content, int $likesCount): void
    {
        $weeklyLikesCount = ContentLike::query()
            ->where('module', $module)
            ->where('content_slug', $content)
            ->where('created_at', '>=', CarbonImmutable::now()->startOfWeek())
            ->count();

        $this->catalogEngagementUpdater->syncContentLikeCounts(
            $this->tenantContext->get(),
            $module,
            $content,
            $likesCount,
            $weeklyLikesCount,
        );
    }

    private function findContent(string $module, string $content): ?object
    {
        $modelClass = $this->modules->modelClass($module);

        if ($modelClass === null) {
            return null;
        }

        return $modelClass::query()
            ->where('slug', $content)
            ->where('is_active', true)
            ->first();
    }
}
