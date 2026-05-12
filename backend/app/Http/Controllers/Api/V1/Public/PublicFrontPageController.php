<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\FrontCmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicFrontPageController extends Controller
{
    private const TTL = 300;

    public function index(Request $request, FrontCmsService $frontCmsService): JsonResponse
    {
        return $this->__invoke($request, $frontCmsService);
    }

    public function __invoke(Request $request, FrontCmsService $frontCmsService): JsonResponse
    {
        if (! $request->has('path')) {
            $payload = Cache::remember('public_front_pages_index', now()->addSeconds(self::TTL), fn (): array => [
                'data' => $frontCmsService->publicPagesIndex(),
            ]);

            return response()->json($payload, 200, [
                'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::TTL, self::TTL),
            ]);
        }

        $path = trim((string) $request->query('path', '/'));
        $normalizedPath = $path === '' ? '/' : $path;
        $payload = Cache::remember(
            sprintf('public_front_page:%s', md5($normalizedPath)),
            now()->addSeconds(self::TTL),
            fn () => $frontCmsService->publicPagePayload($normalizedPath),
        );

        if ($payload === null) {
            return response()->json([
                'message' => 'Page introuvable.',
            ], 404);
        }

        return response()->json([
            'data' => $payload,
        ], 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::TTL, self::TTL),
        ]);
    }
}
