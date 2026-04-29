<?php

namespace App\Http\Middleware;

use App\Services\Auth\TenantTokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantApi
{
    public function __construct(private readonly TenantTokenService $tokenService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $this->extractBearer($request);

        if ($bearer === null) {
            return $this->unauthorized('Token d\'authentification manquant.');
        }

        $apiToken = $this->tokenService->findToken($bearer);

        if ($apiToken === null) {
            return $this->unauthorized('Token invalide ou expiré.');
        }

        $apiToken->touchLastUsed();

        $request->attributes->set('tenant_api_token', $apiToken);
        $request->attributes->set('tenant_user', $apiToken->user);

        return $next($request);
    }

    private function extractBearer(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with((string) $header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr((string) $header, 7));

        return blank($token) ? null : $token;
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], 401);
    }
}
