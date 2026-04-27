<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Http\Controllers\Api\V1\Payments\PaymentWebhookController;
use App\Http\Controllers\Api\V1\PublicPlatformConfigurationController;
use App\Http\Controllers\Api\V1\PublicReferenceDataController;
use App\Http\Controllers\Api\V1\PublicTenantDocumentController;
use App\Http\Controllers\Api\V1\TenantCategoryController;
use App\Http\Controllers\Api\V1\TenantDocumentController;
use App\Http\Controllers\Api\V1\TenantEventController;
use App\Http\Controllers\Api\V1\TenantTagController;
use App\Http\Controllers\Api\V1\TenantSettingController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\Platform\TenantController;
use App\Http\Controllers\Api\V1\Platform\CentralCategoryController;
use App\Http\Controllers\Api\V1\Platform\CentralTagController;
use App\Http\Controllers\Api\V1\Platform\PlanController;
use App\Http\Controllers\Api\V1\Platform\PlatformSettingController;
use App\Http\Controllers\Api\V1\Platform\ReferenceDataController;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthCheckController::class);
    Route::get('/public/platform/configuration', PublicPlatformConfigurationController::class);
    Route::get('/public/references/cities', [PublicReferenceDataController::class, 'cities']);
    Route::get('/public/references/countries', [PublicReferenceDataController::class, 'countries']);
    Route::get('/public/references/currencies', [PublicReferenceDataController::class, 'currencies']);
    Route::get('/public/references/languages', [PublicReferenceDataController::class, 'languages']);
    Route::get('/public/references/payment-method-types', [PublicReferenceDataController::class, 'paymentMethodTypes']);
    Route::get('/public/references/public-statuses', [PublicReferenceDataController::class, 'publicStatuses']);
    Route::get('/public/references/resource-types', [PublicReferenceDataController::class, 'resourceTypes']);

    Route::middleware(['initialize.tenant.route'])->group(function (): void {
        Route::get('/public/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'showPublic']);
        Route::get('/public/tenants/{tenant}/documents', [PublicTenantDocumentController::class, 'index']);
        Route::get('/public/tenants/{tenant}/documents/{document}', [PublicTenantDocumentController::class, 'show']);
    });

    Route::prefix('platform')->group(function (): void {
        Route::get('/categories', [CentralCategoryController::class, 'index']);
        Route::post('/categories', [CentralCategoryController::class, 'store']);
        Route::get('/categories/{category}', [CentralCategoryController::class, 'show']);
        Route::get('/references/cities', [ReferenceDataController::class, 'cities']);
        Route::get('/references/countries', [ReferenceDataController::class, 'countries']);
        Route::get('/references/currencies', [ReferenceDataController::class, 'currencies']);
        Route::get('/references/languages', [ReferenceDataController::class, 'languages']);
        Route::get('/references/payment-method-types', [ReferenceDataController::class, 'paymentMethodTypes']);
        Route::get('/references/public-statuses', [ReferenceDataController::class, 'publicStatuses']);
        Route::get('/references/resource-types', [ReferenceDataController::class, 'resourceTypes']);
        Route::get('/tags', [CentralTagController::class, 'index']);
        Route::post('/tags', [CentralTagController::class, 'store']);
        Route::get('/tags/{tag}', [CentralTagController::class, 'show']);
        Route::get('/plans', [PlanController::class, 'index']);
        Route::post('/plans', [PlanController::class, 'store']);
        Route::get('/plans/{plan}', [PlanController::class, 'show']);
        Route::get('/settings', [PlatformSettingController::class, 'index']);
        Route::put('/settings', [PlatformSettingController::class, 'upsert']);
        Route::post('/tenants/{tenant}/categories/sync', [CentralCategoryController::class, 'syncTenant']);
        Route::post('/tenants/{tenant}/tags/sync', [CentralTagController::class, 'syncTenant']);
        Route::post('/tenants/{tenant}/subscriptions', [PlanController::class, 'assignToTenant']);
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        Route::patch('/tenants/{tenant}/activate', [TenantController::class, 'activate']);
        Route::patch('/tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
        Route::patch('/tenants/{tenant}/archive', [TenantController::class, 'archive']);
    });

    Route::post('/payments/webhooks/{gateway}', PaymentWebhookController::class);

    Route::middleware(['initialize.tenant.route'])->group(function (): void {
        Route::get('/tenants/{tenant}/categories', TenantCategoryController::class);
        Route::get('/tenants/{tenant}/context', TenantContextController::class);
        Route::get('/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'show']);
        Route::get('/tenants/{tenant}/settings', [TenantSettingController::class, 'index']);
        Route::get('/tenants/{tenant}/tags', TenantTagController::class);
        Route::get('/tenants/{tenant}/documents', [TenantDocumentController::class, 'index']);
        Route::post('/tenants/{tenant}/documents', [TenantDocumentController::class, 'store']);
        Route::get('/tenants/{tenant}/documents/{document}', [TenantDocumentController::class, 'show']);
        Route::get('/tenants/{tenant}/events', [TenantEventController::class, 'index']);
        Route::post('/tenants/{tenant}/events', [TenantEventController::class, 'store']);
        Route::get('/tenants/{tenant}/events/{event}', [TenantEventController::class, 'show']);
        Route::put('/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'upsert']);
        Route::put('/tenants/{tenant}/settings', [TenantSettingController::class, 'upsert']);
    });
});
