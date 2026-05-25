<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\PublicCatalog\Contracts\FrontContent;

class PublicFrontPageController extends Controller
{
    public function index(Request $request, FrontContent $frontContent): JsonResponse
    {
        return $this->__invoke($request, $frontContent);
    }

    public function __invoke(Request $request, FrontContent $frontContent): JsonResponse
    {
        if (! $request->has('path')) {
            $payload = [
                'data' => $frontContent->publicPagesIndex(),
            ];

            return response()->json($payload, 200, [
                'Cache-Control' => 'no-store, private',
            ]);
        }

        $path = trim((string) $request->query('path', '/'));
        $normalizedPath = $path === '' ? '/' : $path;
        $payload = $frontContent->publicPagePayload($normalizedPath);

        if ($payload === null) {
            return response()->json([
                'message' => 'Page introuvable.',
            ], 404);
        }

        return response()->json([
            'data' => $payload,
        ], 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
