<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Health\HealthProbe;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckController extends Controller
{
    public function __construct(private readonly HealthProbe $probe) {}

    public function __invoke(): JsonResponse
    {
        $checks = $this->probe->run();
        $healthy = $this->probe->isHealthy($checks);

        return response()->json([
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
