<?php

use App\Http\Controllers\Api\V1\Auth\PlatformAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantAuthController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Http\Controllers\Api\V1\Payments\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Public\PublicAccessPassController;
use App\Http\Controllers\Api\V1\Public\PublicCallForProjectSubmissionController;
use App\Http\Controllers\Api\V1\Public\PublicContentController;
use App\Http\Controllers\Api\V1\Public\PublicFrontPageController;
use App\Http\Controllers\Api\V1\Public\PublicOnboardingController;
use App\Http\Controllers\Api\V1\Public\PublicPaymentController;
use App\Http\Controllers\Api\V1\Public\PublicReceiptVerificationController;
use App\Http\Controllers\Api\V1\PublicPlatformConfigurationController;
use App\Http\Controllers\Api\V1\PublicReferenceDataController;
use App\Http\Controllers\Api\V1\PublicTenantDocumentController;
use App\Http\Controllers\Api\V1\TenantAccessPassCheckinController;
use App\Http\Controllers\Api\V1\TenantAccessPassController;
use App\Http\Controllers\Api\V1\TenantCategoryController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\TenantDocumentController;
use App\Http\Controllers\Api\V1\TenantEventController;
use App\Http\Controllers\Api\V1\TenantEventLikeController;
use App\Http\Controllers\Api\V1\TenantOrganizationFollowController;
use App\Http\Controllers\Api\V1\TenantOrderController;
use App\Http\Controllers\Api\V1\TenantReceiptController;
use App\Http\Controllers\Api\V1\TenantSettingController;
use App\Http\Controllers\Api\V1\TenantTagController;
use App\Http\Controllers\Api\V1\Platform\CentralCategoryController;
use App\Http\Controllers\Api\V1\Platform\CentralTagController;
use App\Http\Controllers\Api\V1\Platform\PlanController;
use App\Http\Controllers\Api\V1\Platform\PlatformSettingController;
use App\Http\Controllers\Api\V1\Platform\ReferenceDataController;
use App\Http\Controllers\Api\V1\Platform\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // ─── Santé ────────────────────────────────────────────────────────────
    Route::get('/health', HealthCheckController::class);

    // ─── Références publiques ─────────────────────────────────────────────
    Route::prefix('public')->group(function (): void {
        Route::get('/platform/configuration', [PublicPlatformConfigurationController::class, 'show']);
        Route::get('/content', [PublicContentController::class, 'globalIndex']);
        Route::get('/content/filters', [PublicContentController::class, 'globalFilters']);
        Route::get('/content/categories', [PublicContentController::class, 'globalCategoryOverview']);
        Route::get('/content/cities', [PublicContentController::class, 'globalCityOverview']);
        Route::get('/content/speakers', [PublicContentController::class, 'globalSpeakerHighlights']);
        Route::get('/content/summary', [PublicContentController::class, 'globalSummary']);
        Route::get('/content/search/suggestions', [PublicContentController::class, 'globalSearchSuggestions']);
        Route::get('/content/{module}/{slug}/related', [PublicContentController::class, 'globalRelated']);
        Route::get('/content/{module}/{slug}', [PublicContentController::class, 'globalShow']);
        Route::get('/front/pages', [PublicFrontPageController::class, 'index']);
        Route::get('/references/countries', [PublicReferenceDataController::class, 'countries']);
        Route::get('/references/countries/{country}/states', [PublicReferenceDataController::class, 'states']);
        Route::get('/references/countries/{country}/cities', [PublicReferenceDataController::class, 'cities']);
        Route::get('/references/cities/search', [PublicReferenceDataController::class, 'searchCities']);
        Route::get('/resource-types', [PublicReferenceDataController::class, 'resourceTypes']);
    });

    // ─── Ressources publiques tenant (lecture seule, sans auth) ───────────
    Route::middleware(['initialize.tenant.route'])->group(function (): void {
        Route::get('/public/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'showPublic']);
        Route::get('/public/tenants/{tenant}/organization-profile/catalog', [OrganizationProfileController::class, 'showPublicCatalog']);
        Route::get('/public/tenants/{tenant}/documents', [PublicTenantDocumentController::class, 'index']);
        Route::get('/public/tenants/{tenant}/documents/{document}', [PublicTenantDocumentController::class, 'show']);
        Route::get('/public/tenants/{tenant}/access-passes/{code}', [PublicAccessPassController::class, 'show'])
            ->middleware('throttle:public-pass-lookup');
        Route::get('/public/tenants/{tenant}/receipts/{receipt}/verify', [PublicReceiptVerificationController::class, 'show'])
            ->middleware('throttle:public-pass-lookup');
        Route::get('/public/tenants/{tenant}/content', [PublicContentController::class, 'index']);
        Route::get('/public/tenants/{tenant}/content/filters', [PublicContentController::class, 'filters']);
        Route::get('/public/tenants/{tenant}/content/{module}/{slug}', [PublicContentController::class, 'show']);
        Route::post('/public/tenants/{tenant}/calls-for-projects/{callForProject}/applications', PublicCallForProjectSubmissionController::class)
            ->middleware('throttle:public-call-for-project-apply');
        Route::get('/public/tenants/{tenant}/payment-options', [PublicPaymentController::class, 'options']);
        Route::post('/public/tenants/{tenant}/payments/initialize', [PublicPaymentController::class, 'initialize'])
            ->middleware('throttle:public-payment-initialize');
        Route::get('/public/tenants/{tenant}/payments/verify/{reference}', [PublicPaymentController::class, 'verify'])
            ->middleware('throttle:public-payment-verify');
    });

    // ─── Webhooks paiement (sécurisé par signature HMAC Paystack) ─────────
    Route::post('/payments/webhooks/{gateway}', PaymentWebhookController::class)
        ->middleware('throttle:payment-webhooks');

    // ─── Authentification Platform ─────────────────────────────────────────
    Route::prefix('platform/auth')->group(function (): void {
        Route::post('/login', [PlatformAuthController::class, 'login'])
            ->middleware('throttle:platform-auth');
        Route::middleware(['auth.platform.api'])->group(function (): void {
            Route::post('/logout', [PlatformAuthController::class, 'logout']);
            Route::get('/me', [PlatformAuthController::class, 'me']);
        });
    });

    // ─── API Platform (Bearer token requis) ───────────────────────────────
    Route::prefix('platform')->middleware(['auth.platform.api'])->group(function (): void {
        Route::get('/categories', [CentralCategoryController::class, 'index']);
        Route::post('/categories', [CentralCategoryController::class, 'store']);
        Route::get('/categories/{category}', [CentralCategoryController::class, 'show']);

        Route::prefix('references')->group(function (): void {
            Route::get('/cities', [ReferenceDataController::class, 'cities']);
            Route::get('/countries', [ReferenceDataController::class, 'countries']);
            Route::get('/currencies', [ReferenceDataController::class, 'currencies']);
            Route::get('/languages', [ReferenceDataController::class, 'languages']);
            Route::get('/payment-method-types', [ReferenceDataController::class, 'paymentMethodTypes']);
            Route::get('/public-statuses', [ReferenceDataController::class, 'publicStatuses']);
            Route::get('/resource-types', [ReferenceDataController::class, 'resourceTypes']);
        });

        Route::get('/tags', [CentralTagController::class, 'index']);
        Route::post('/tags', [CentralTagController::class, 'store']);
        Route::get('/tags/{tag}', [CentralTagController::class, 'show']);

        Route::get('/plans', [PlanController::class, 'index']);
        Route::post('/plans', [PlanController::class, 'store']);
        Route::get('/plans/{plan}', [PlanController::class, 'show']);

        Route::get('/settings', [PlatformSettingController::class, 'index']);
        Route::put('/settings', [PlatformSettingController::class, 'upsert']);

        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        Route::patch('/tenants/{tenant}/activate', [TenantController::class, 'activate']);
        Route::patch('/tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
        Route::patch('/tenants/{tenant}/archive', [TenantController::class, 'archive']);
        Route::post('/tenants/{tenant}/categories/sync', [CentralCategoryController::class, 'syncTenant']);
        Route::post('/tenants/{tenant}/tags/sync', [CentralTagController::class, 'syncTenant']);
        Route::post('/tenants/{tenant}/subscriptions', [PlanController::class, 'assignToTenant']);
    });

    // ─── Onboarding organisateur (public, sans auth) ──────────────────────
    Route::post('/public/onboarding/register', [PublicOnboardingController::class, 'register'])
        ->middleware('throttle:public-onboarding');

    // ─── Authentification Tenant ───────────────────────────────────────────
    Route::prefix('tenants/{tenant}/auth')->middleware(['initialize.tenant.route'])->group(function (): void {
        Route::post('/login', [TenantAuthController::class, 'login'])
            ->middleware('throttle:tenant-auth');
        Route::post('/register', [TenantAuthController::class, 'register'])
            ->middleware('throttle:tenant-auth');
        Route::post('/forgot-password', [TenantAuthController::class, 'forgotPassword'])
            ->middleware('throttle:tenant-auth');
        Route::post('/reset-password', [TenantAuthController::class, 'resetPassword'])
            ->middleware('throttle:tenant-auth');
        Route::get('/email/verify/{id}/{hash}', [TenantAuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:tenant-auth'])
            ->name('tenant.email.verify');
        Route::middleware(['auth.tenant.api'])->group(function (): void {
            Route::post('/logout', [TenantAuthController::class, 'logout']);
            Route::get('/me', [TenantAuthController::class, 'me']);
            Route::put('/me', [TenantAuthController::class, 'updateMe']);
            Route::post('/email/verification-notification', [TenantAuthController::class, 'sendVerificationNotification'])
                ->middleware('throttle:tenant-auth');
            Route::get('/avatar', [TenantAuthController::class, 'avatar']);
            Route::post('/avatar', [TenantAuthController::class, 'updateAvatar']);
            Route::put('/password', [TenantAuthController::class, 'updatePassword']);
            Route::get('/notifications', [TenantAuthController::class, 'notifications']);
            Route::patch('/notifications/read-all', [TenantAuthController::class, 'markAllNotificationsAsRead']);
            Route::patch('/notifications/{notification}/read', [TenantAuthController::class, 'markNotificationAsRead']);
        });
    });

    // ─── API Tenant (Bearer token requis) ──────────────────────────────────
    Route::middleware(['initialize.tenant.route', 'auth.tenant.api'])->prefix('tenants/{tenant}')->group(function (): void {
        Route::get('/categories', TenantCategoryController::class);
        Route::get('/context', TenantContextController::class);
        Route::get('/organization-profile', [OrganizationProfileController::class, 'show']);
        Route::put('/organization-profile', [OrganizationProfileController::class, 'upsert']);
        Route::get('/organization-profile/follow', [TenantOrganizationFollowController::class, 'show']);
        Route::post('/organization-profile/follow', [TenantOrganizationFollowController::class, 'store'])
            ->middleware('throttle:tenant-auth');
        Route::delete('/organization-profile/follow', [TenantOrganizationFollowController::class, 'destroy'])
            ->middleware('throttle:tenant-auth');
        Route::get('/settings', [TenantSettingController::class, 'index']);
        Route::put('/settings', [TenantSettingController::class, 'upsert']);
        Route::get('/tags', TenantTagController::class);

        Route::get('/documents', [TenantDocumentController::class, 'index']);
        Route::post('/documents', [TenantDocumentController::class, 'store']);
        Route::get('/documents/{document}', [TenantDocumentController::class, 'show']);

        Route::get('/events', [TenantEventController::class, 'index']);
        Route::post('/events', [TenantEventController::class, 'store']);
        Route::get('/events/likes', [TenantEventLikeController::class, 'index']);
        Route::get('/events/{event}', [TenantEventController::class, 'show']);
        Route::get('/events/{event}/like', [TenantEventLikeController::class, 'show']);
        Route::post('/events/{event}/like', [TenantEventLikeController::class, 'store'])
            ->middleware('throttle:tenant-auth');
        Route::delete('/events/{event}/like', [TenantEventLikeController::class, 'destroy'])
            ->middleware('throttle:tenant-auth');

        Route::get('/orders', [TenantOrderController::class, 'index']);
        Route::get('/orders/{order}', [TenantOrderController::class, 'show']);

        Route::get('/receipts', [TenantReceiptController::class, 'index']);
        Route::get('/receipts/{receipt}', [TenantReceiptController::class, 'show']);

        Route::get('/access-passes', [TenantAccessPassController::class, 'index']);
        Route::get('/access-passes/{accessPass}', [TenantAccessPassController::class, 'show']);

        // Check-in
        Route::prefix('access-passes/{accessPass}/checkin')->group(function (): void {
            Route::get('/preview', [TenantAccessPassCheckinController::class, 'preview'])
                ->middleware('throttle:tenant-checkin');
            Route::post('/consume', [TenantAccessPassCheckinController::class, 'consume'])
                ->middleware('throttle:tenant-checkin');
            Route::post('/reset', [TenantAccessPassCheckinController::class, 'reset'])
                ->middleware('throttle:tenant-checkin');
            Route::post('/revoke', [TenantAccessPassCheckinController::class, 'revoke'])
                ->middleware('throttle:tenant-checkin');
            Route::post('/reactivate', [TenantAccessPassCheckinController::class, 'reactivate'])
                ->middleware('throttle:tenant-checkin');
        });
    });
});
