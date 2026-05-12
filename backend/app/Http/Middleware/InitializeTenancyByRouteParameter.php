<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\RouteTenantResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InitializeTenancyByRouteParameter extends IdentificationMiddleware
{
    public static $onFail;

    public function __construct(Tenancy $tenancy, RouteTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $tenantIdentifier = $route?->parameter('tenant') ?? $this->resolveTenantIdentifierFromRequest($request);

        if (is_string($tenantIdentifier) && in_array(urldecode($tenantIdentifier), ['{tenant}', '%7Btenant%7D'], true)) {
            $tenantIdentifier = $this->resolveTenantIdentifierFromRequest($request) ?? tenancy()->tenant?->slug;

            if ($route !== null && filled($tenantIdentifier)) {
                $route->setParameter('tenant', $tenantIdentifier);
            }
        }

        if (! filled($tenantIdentifier)) {
            return $this->tenantNotFound($request);
        }

        try {
            if ($route !== null && $route->parameter('tenant') === null) {
                $route->setParameter('tenant', $tenantIdentifier);
            }

            $tenant = $route !== null
                ? $this->resolver->resolve($route)
                : $this->resolveTenantModel($tenantIdentifier);

            URL::defaults([
                'tenant' => data_get($tenant, 'slug') ?: $tenant->getTenantKey(),
            ]);

            $this->tenancy->initialize($tenant);

            return $next($request);
        } catch (\Throwable $exception) {
            return $this->tenantNotFound($request);
        }
    }

    private function tenantNotFound(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return new JsonResponse([
                'message' => 'Tenant context is required.',
            ], 404);
        }

        throw new NotFoundHttpException('Tenant introuvable.');
    }

    private function resolveTenantIdentifierFromRequest(Request $request): string|int|null
    {
        $livewireSnapshot = data_get($request->input('components', []), '0.snapshot');

        if (is_string($livewireSnapshot) && $livewireSnapshot !== '') {
            try {
                $snapshot = json_decode($livewireSnapshot, true, 512, JSON_THROW_ON_ERROR);
                $candidate = $this->extractTenantIdentifierFromUrl(
                    data_get($snapshot, 'memo.path')
                    ?? data_get($snapshot, 'memo.url')
                    ?? data_get($snapshot, 'memo.uri'),
                );

                if (filled($candidate)) {
                    return $candidate;
                }
            } catch (\Throwable) {
                // Ignore malformed snapshots and continue with header-based fallbacks.
            }
        }

        foreach ([
            $request->headers->get('referer'),
            $request->headers->get('origin'),
            $request->fullUrl(),
            $request->path(),
        ] as $candidateUrl) {
            $candidate = $this->extractTenantIdentifierFromUrl($candidateUrl);

            if (filled($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractTenantIdentifierFromUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = $url;
        }

        if (! preg_match('#/tenants/([^/]+)/admin(?:/.*)?$#i', $path, $matches)) {
            return null;
        }

        return urldecode((string) ($matches[1] ?? ''));
    }

    private function resolveTenantModel(string|int $identifier): Tenant
    {
        $tenantModel = config('tenancy.tenant_model');

        $tenant = app($tenantModel)
            ->newQuery()
            ->where(function ($query) use ($identifier): void {
                if (is_numeric($identifier)) {
                    $query->whereKey($identifier);
                }

                if (is_string($identifier) && Str::isUuid($identifier)) {
                    $query->orWhere('public_id', $identifier);
                }

                $query->orWhere('slug', $identifier);
            })
            ->first();

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        throw new NotFoundHttpException('Tenant introuvable.');
    }
}
