<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\SubscriptionGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('tenant')->check()) {
            return $next($request);
        }

        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404, 'Tenant introuvable.');
        }

        if (! app(SubscriptionGateService::class)->allowsPanel($tenant)) {
            abort(402, 'L’accès au backoffice est bloqué par la souscription ou les feature flags.');
        }

        return $next($request);
    }
}
