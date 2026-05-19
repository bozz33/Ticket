<?php

use App\Http\Controllers\Api\V1\Auth\PlatformAuthController;
use App\Http\Controllers\Api\V1\Platform\CentralCategoryController;
use App\Http\Controllers\Api\V1\Platform\CentralTagController;
use App\Http\Controllers\Api\V1\Platform\PlanController;
use App\Http\Controllers\Api\V1\Platform\PlatformSettingController;
use App\Http\Controllers\Api\V1\Platform\ReferenceDataController;
use App\Http\Controllers\Api\V1\Platform\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform/auth')->group(function (): void {
    Route::post('/login', [PlatformAuthController::class, 'login'])
        ->middleware('throttle:platform-auth');

    Route::middleware(['auth.platform.api'])->group(function (): void {
        Route::post('/logout', [PlatformAuthController::class, 'logout']);
        Route::get('/me', [PlatformAuthController::class, 'me']);
    });
});

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
