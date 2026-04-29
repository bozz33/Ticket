<?php

use App\Http\Controllers\Api\V1\Auth\PlatformAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantAuthController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Http\Controllers\Api\V1\Payments\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Public\PublicAccessPassController;
use App\Http\Controllers\Api\V1\PublicPlatformConfigurationController;
use App\Http\Controllers\Api\V1\PublicReferenceDataController;
use App\Http\Controllers\Api\V1\PublicTenantDocumentController;
use App\Http\Controllers\Api\V1\TenantAccessPassCheckinController;
use App\Http\Controllers\Api\V1\TenantAccessPassController;
use App\Http\Controllers\Api\V1\TenantCategoryController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\TenantDocumentController;
use App\Http\Controllers\Api\V1\TenantEventController;
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
    Route::get('/public/platform/configuration', PublicPlatformConfigurationController::class);
    Route::prefix('public/references')->group(function (): void {
        Route::get('/cities', [PublicReferenceDataController::class, 'cities']);
        Route::get('/countries', [PublicReferenceDataController::class, 'countries']);
        Route::get('/currencies', [PublicReferenceDataController::class, 'currencies']);
        Route::get('/languages', [PublicReferenceDataController::class, 'languages']);
        Route::get('/payment-method-types', [PublicReferenceDataController::class, 'paymentMethodTypes']);
        Route::get('/public-statuses', [PublicReferenceDataController::class, 'publicStatuses']);
        Route::get('/resource-types', [PublicReferenceDataController::class, 'resourceTypes']);
    });

    // ─── Ressources publiques tenant (lecture seule, sans auth) ───────────
    Route::middleware(['initialize.tenant.route'])->group(function (): void {
        Route::get('/public/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'showPublic']);
        Route::get('/public/tenants/{tenant}/documents', [PublicTenantDocumentController::class, 'index']);
        Route::get('/public/tenants/{tenant}/documents/{document}', [PublicTenantDocumentController::class, 'show']);
        Route::get('/public/tenants/{tenant}/access-passes/{code}', [PublicAccessPassController::class, 'show']);
    });

    // ─── Webhooks paiement (sécurisé par signature HMAC Paystack) ─────────
    Route::post('/payments/webhooks/{gateway}', PaymentWebhookController::class);

    // ─── Authentification Platform ─────────────────────────────────────────
    Route::prefix('platform/auth')->group(function (): void {
        Route::post('/login', [PlatformAuthController::class, 'login']);
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

    // ─── Authentification Tenant ───────────────────────────────────────────
    Route::prefix('tenants/{tenant}/auth')->middleware(['initialize.tenant.route'])->group(function (): void {
        Route::post('/login', [TenantAuthController::class, 'login']);
        Route::middleware(['auth.tenant.api'])->group(function (): void {
            Route::post('/logout', [TenantAuthController::class, 'logout']);
            Route::get('/me', [TenantAuthController::class, 'me']);
        });
    });

    // ─── API Tenant (Bearer token requis) ──────────────────────────────────
    Route::middleware(['initialize.tenant.route', 'auth.tenant.api'])->prefix('tenants/{tenant}')->group(function (): void {
        Route::get('/categories', TenantCategoryController::class);
        Route::get('/context', TenantContextController::class);
        Route::get('/organization-profile', [OrganizationProfileController::class, 'show']);
        Route::put('/organization-profile', [OrganizationProfileController::class, 'upsert']);
        Route::get('/settings', [TenantSettingController::class, 'index']);
        Route::put('/settings', [TenantSettingController::class, 'upsert']);
        Route::get('/tags', TenantTagController::class);

        Route::get('/documents', [TenantDocumentController::class, 'index']);
        Route::post('/documents', [TenantDocumentController::class, 'store']);
        Route::get('/documents/{document}', [TenantDocumentController::class, 'show']);

        Route::get('/events', [TenantEventController::class, 'index']);
        Route::post('/events', [TenantEventController::class, 'store']);
        Route::get('/events/{event}', [TenantEventController::class, 'show']);

        Route::get('/orders', [TenantOrderController::class, 'index']);
        Route::get('/orders/{order}', [TenantOrderController::class, 'show']);

        Route::get('/receipts', [TenantReceiptController::class, 'index']);
        Route::get('/receipts/{receipt}', [TenantReceiptController::class, 'show']);

        Route::get('/access-passes', [TenantAccessPassController::class, 'index']);
        Route::get('/access-passes/{accessPass}', [TenantAccessPassController::class, 'show']);

        // Check-in
        Route::prefix('access-passes/{accessPass}/checkin')->group(function (): void {
            Route::get('/preview', [TenantAccessPassCheckinController::class, 'preview']);
            Route::post('/consume', [TenantAccessPassCheckinController::class, 'consume']);
            Route::post('/reset', [TenantAccessPassCheckinController::class, 'reset']);
            Route::post('/revoke', [TenantAccessPassCheckinController::class, 'revoke']);
            Route::post('/reactivate', [TenantAccessPassCheckinController::class, 'reactivate']);
        });
    });
});
