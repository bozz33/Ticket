<?php

namespace App\Providers;

use App\Http\Middleware\EnsureTenantCategoriesAreSynced;
use App\Http\Middleware\InitializeTenancyByRouteParameter;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\FilamentLoginResponse;
use App\Models\CompliancePolicy;
use App\Models\FeatureFlag;
use App\Models\FinancialExport;
use App\Models\GatewayWebhookLog;
use App\Models\IncidentLog;
use App\Models\KpiSnapshot;
use App\Models\PaymentGateway;
use App\Models\PaymentIncident;
use App\Models\PayoutBatch;
use App\Models\PlatformSetting;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\ReconciliationLog;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Observers\PlatformAuditObserver;
use App\Services\FeatureFlagService;
use App\Services\PlatformMailSettings;
use App\Services\PlatformSettingsService;
use App\Support\Microservices\MicroserviceClientFactory;
use App\Support\Microservices\MicroserviceRegistry;
use App\Support\Tenancy\TenantContext;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, FilamentLoginResponse::class);

        $this->app->singleton(FeatureFlagService::class, fn (): FeatureFlagService => new FeatureFlagService);
        $this->app->singleton(PlatformSettingsService::class, fn (): PlatformSettingsService => new PlatformSettingsService);
        $this->app->singleton(TenantContext::class, fn (): TenantContext => new TenantContext);
        $this->app->singleton(MicroserviceRegistry::class, fn (): MicroserviceRegistry => new MicroserviceRegistry);
        $this->app->singleton(MicroserviceClientFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->name('ticket.livewire.update');
        });

        Livewire::addPersistentMiddleware([
            InitializeTenancyByRouteParameter::class,
            EnsureTenantCategoriesAreSynced::class,
        ]);

        $this->configureRateLimiting();

        app(PlatformMailSettings::class)->apply();

        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            $baseUrl = rtrim((string) config('ticket.public_frontend_url', config('app.url')), '/');
            $email = method_exists($user, 'getEmailForPasswordReset')
                ? $user->getEmailForPasswordReset()
                : ($user->email ?? '');

            return sprintf(
                '%s/compte/reinitialisation/nouveau?token=%s&email=%s',
                $baseUrl,
                urlencode($token),
                urlencode((string) $email),
            );
        });

        VerifyEmail::createUrlUsing(function (object $user): string {
            $tenantSlug = (string) (app(TenantContext::class)->get()?->slug ?? request()->route('tenant', ''));

            return URL::temporarySignedRoute(
                'tenant.email.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'tenant' => $tenantSlug,
                    'id' => $user->getKey(),
                    'hash' => sha1((string) $user->getEmailForVerification()),
                ],
            );
        });

        foreach ([
            CompliancePolicy::class,
            FeatureFlag::class,
            FinancialExport::class,
            GatewayWebhookLog::class,
            IncidentLog::class,
            KpiSnapshot::class,
            PaymentGateway::class,
            PaymentIncident::class,
            PlatformSetting::class,
            PlatformSupportTicket::class,
            PlatformUser::class,
            PlatformTransaction::class,
            PayoutBatch::class,
            ReconciliationLog::class,
            Settlement::class,
            Tenant::class,
        ] as $model) {
            $model::observe(PlatformAuditObserver::class);
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('platform-auth', function (Request $request): array {
            $email = Str::lower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.platform_auth_per_minute', 5),
                    sprintf('platform-auth:%s:%s', $email, $ip),
                ),
            ];
        });

        RateLimiter::for('tenant-auth', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $email = Str::lower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.tenant_auth_per_minute', 5),
                    sprintf('tenant-auth:%s:%s:%s', $tenant, $email, $ip),
                ),
            ];
        });

        RateLimiter::for('tenant-engagement', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $tenantUser = $request->attributes->get('tenant_user');
            $userId = is_object($tenantUser) && method_exists($tenantUser, 'getKey')
                ? (string) $tenantUser->getKey()
                : 'guest';
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.tenant_engagement_per_minute', 120),
                    sprintf('tenant-engagement:%s:%s:%s', $tenant, $userId, $ip),
                ),
            ];
        });

        RateLimiter::for('public-onboarding', function (Request $request): array {
            $email = Str::lower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.public_onboarding_per_minute', 3),
                    sprintf('public-onboarding:%s:%s', $email, $ip),
                ),
            ];
        });

        RateLimiter::for('public-call-for-project-apply', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $callForProject = (string) $request->route('callForProject', 'call');
            $email = Str::lower((string) $request->input('responses.email', 'guest'));
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.public_call_for_project_apply_per_minute', 5),
                    sprintf('public-call-for-project-apply:%s:%s:%s:%s', $tenant, $callForProject, $email, $ip),
                ),
            ];
        });

        RateLimiter::for('public-payment-initialize', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $offer = (string) $request->input('offer', 'offer');
            $email = Str::lower((string) $request->input('buyer_email', 'guest'));
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.public_payment_initialize_per_minute', 10),
                    sprintf('public-payment-initialize:%s:%s:%s:%s', $tenant, $offer, $email, $ip),
                ),
            ];
        });

        RateLimiter::for('public-payment-verify', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $reference = (string) $request->route('reference', 'reference');
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.public_payment_verify_per_minute', 30),
                    sprintf('public-payment-verify:%s:%s:%s', $tenant, $reference, $ip),
                ),
            ];
        });

        RateLimiter::for('public-pass-lookup', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $code = (string) $request->route('code', 'code');
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.public_pass_lookup_per_minute', 30),
                    sprintf('public-pass-lookup:%s:%s:%s', $tenant, $code, $ip),
                ),
            ];
        });

        RateLimiter::for('payment-webhooks', function (Request $request): array {
            $gateway = (string) $request->route('gateway', 'gateway');
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.payment_webhooks_per_minute', 120),
                    sprintf('payment-webhooks:%s:%s', $gateway, $ip),
                ),
            ];
        });

        RateLimiter::for('tenant-checkin', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $accessPass = (string) $request->route('accessPass', 'access-pass');
            $ip = (string) $request->ip();

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.tenant_checkin_per_minute', 60),
                    sprintf('tenant-checkin:%s:%s:%s', $tenant, $accessPass, $ip),
                ),
            ];
        });

        RateLimiter::for('tenant-refund', function (Request $request): array {
            $tenant = (string) $request->route('tenant', 'tenant');
            $ip = (string) $request->ip();
            // Use user ID if available to prevent bypassing via IP rotation.
            $userId = (string) ($request->attributes->get('tenant_user')?->id ?? $ip);

            return [
                $this->perMinuteLimit(
                    (int) config('ticket.rate_limits.tenant_refund_per_minute', 5),
                    sprintf('tenant-refund:%s:%s', $tenant, $userId),
                ),
            ];
        });
    }

    private function perMinuteLimit(int $maxAttempts, string $key): Limit
    {
        return Limit::perMinute(max(1, $maxAttempts))
            ->by($key)
            ->response(function (Request $request, array $headers) {
                return ApiResponse::error('Trop de tentatives. Réessayez plus tard.', 429, [], $headers);
            });
    }
}
