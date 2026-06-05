<?php

namespace App\Http\Middleware;

use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    /**
     * Verify that the request's API token holds all of the given abilities.
     * Must run after auth.tenant.api, which sets the tenant_api_token attribute.
     *
     * Usage in routes:
     *   ->middleware('ability:passes.scan')
     *   ->middleware('ability:passes.manage')
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $token = $request->attributes->get('tenant_api_token');

        if (! $token instanceof UserApiToken) {
            return new JsonResponse(['message' => 'Token d\'authentification requis.'], 401);
        }

        foreach ($abilities as $ability) {
            if (! $token->can($ability)) {
                return new JsonResponse(['message' => 'Action non autorisée pour ce token.'], 403);
            }
        }

        return $next($request);
    }
}
