<?php

namespace App\Support\Observability;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Central, resilient sink for the observability module. Persists structured error and
 * telemetry records (backend exceptions and forwarded frontend events) to the central
 * `error_logs` table. Every write is guarded: observability must never break the request
 * it observes, so failures (missing table, DB down) are swallowed silently.
 */
class ErrorLogWriter
{
    public static function record(array $attributes): void
    {
        try {
            if (! Schema::connection('central')->hasTable('error_logs')) {
                return;
            }

            ErrorLog::query()->create(array_merge([
                'public_id' => (string) Str::uuid(),
                'level' => 'error',
                'source' => 'backend',
                'occurred_at' => now(),
            ], $attributes));
        } catch (Throwable) {
            // Observability must never break the request it is observing.
        }
    }

    public static function fromException(Throwable $exception, ?Request $request = null): void
    {
        $request ??= request();

        self::record([
            'request_id' => $request?->attributes->get('request_id'),
            'type' => $exception::class,
            'message' => Str::limit($exception->getMessage(), 1000, ''),
            'exception_class' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'status_code' => self::statusCode($exception),
            'tenant_id' => self::tenantId(),
            'user_id' => self::userId($request),
            'method' => $request?->method(),
            'url' => $request ? Str::limit($request->fullUrl(), 1000, '') : null,
            'route' => $request?->route()?->getName() ?: $request?->route()?->uri(),
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'trace' => Str::limit($exception->getTraceAsString(), 8000, ''),
        ]);
    }

    /**
     * Context merged into every exception log line (file channels), so file logs and the
     * DB table share the same correlation id.
     */
    public static function context(?Request $request = null): array
    {
        $request ??= request();

        return array_filter([
            'request_id' => $request?->attributes->get('request_id'),
            'tenant_id' => self::tenantId(),
            'user_id' => self::userId($request),
        ], static fn ($value): bool => $value !== null);
    }

    private static function statusCode(Throwable $exception): ?int
    {
        return method_exists($exception, 'getStatusCode') ? (int) $exception->getStatusCode() : null;
    }

    private static function tenantId(): ?int
    {
        try {
            $tenant = function_exists('tenant') ? tenant() : null;

            return $tenant?->getKey() !== null ? (int) $tenant->getKey() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function userId(?Request $request): ?int
    {
        if ($request === null) {
            return null;
        }

        $tenantUser = $request->attributes->get('tenant_user');

        if (is_object($tenantUser) && isset($tenantUser->id)) {
            return (int) $tenantUser->id;
        }

        $identifier = $request->user()?->getAuthIdentifier();

        return $identifier !== null ? (int) $identifier : null;
    }
}
