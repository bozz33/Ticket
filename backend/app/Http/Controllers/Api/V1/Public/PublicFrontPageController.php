<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Ticket\PublicCatalog\Contracts\FrontContent;

class PublicFrontPageController extends Controller
{
    private const TTL = 300;

    public function index(Request $request, FrontContent $frontContent): JsonResponse
    {
        return $this->__invoke($request, $frontContent);
    }

    public function __invoke(Request $request, FrontContent $frontContent): JsonResponse
    {
        if (! $request->has('path')) {
            $payload = Cache::remember('public_front_pages_index', now()->addSeconds(self::TTL), fn (): array => [
                'data' => $frontContent->publicPagesIndex(),
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
            fn () => $frontContent->publicPagePayload($normalizedPath),
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
