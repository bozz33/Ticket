<?php

declare(strict_types=1);

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\AuthenticatePlatformApi;
use App\Http\Middleware\AuthenticateTenantApi;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\EnsureTenantCategoriesAreSynced;
use App\Http\Middleware\InitializeTenancyByRouteParameter;
use App\Http\Responses\ApiResponse;
use App\Support\Observability\ErrorLogWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.platform.api' => AuthenticatePlatformApi::class,
            'auth.tenant.api' => AuthenticateTenantApi::class,
            'ability' => CheckTokenAbility::class,
            'initialize.tenant.route' => InitializeTenancyByRouteParameter::class,
            'tenant.categories.synced' => EnsureTenantCategoriesAreSynced::class,
            'signed' => ValidateSignature::class,
        ]);

        // Correlate every request (and its logs / error_logs rows) with an id.
        $middleware->append(AssignRequestId::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('ticket:release-expired-ticket-reservations --all-tenants --minutes=20 --limit=250')
            ->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson(),
        );

        // Observability module: enrich file logs with correlation context and persist
        // every reportable exception to the queryable central error_logs table.
        $exceptions->context(static fn (): array => ErrorLogWriter::context());

        $exceptions->report(static function (Throwable $exception): void {
            ErrorLogWriter::fromException($exception);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::validation($exception);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Non authentifié.', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Action non autorisée.', 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Ressource introuvable.', 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Ressource introuvable.', 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Trop de tentatives. Réessayez plus tard.',
                429,
                [],
                $exception->getHeaders(),
            );
        });

        $exceptions->render(function (HttpResponseException $exception, Request $request) {
            return $exception->getResponse();
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                config('app.debug') ? $exception->getMessage() : 'Erreur serveur.',
                500,
            );
        });
    })
    ->create();
