<?php

namespace App\Http\Controllers\Api\V1\Observability;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Observability\ClientEventRequest;
use App\Support\Observability\ErrorLogWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Persists frontend observability events (client errors, unhandled rejections, web
 * vitals) so client-side failures are captured server-side instead of being dropped.
 */
class ClientEventController extends Controller
{
    public function __invoke(ClientEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $isError = $data['type'] !== 'web-vital';

        ErrorLogWriter::record([
            'request_id' => $request->headers->get('X-Request-Id'),
            'source' => 'frontend',
            'level' => $isError ? 'error' : 'info',
            'type' => $data['type'],
            'message' => Str::limit((string) ($data['message'] ?? $data['name'] ?? $data['type']), 1000, ''),
            'url' => $data['path'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'context' => $data,
            'trace' => $data['stack'] ?? null,
        ]);

        return response()->json(['accepted' => true], 202);
    }
}
