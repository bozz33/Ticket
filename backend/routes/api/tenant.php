<?php

use App\Http\Controllers\Api\V1\Auth\TenantAuthController;
use App\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Http\Controllers\Api\V1\TenantAccessPassCheckinController;
use App\Http\Controllers\Api\V1\TenantAccessPassController;
use App\Http\Controllers\Api\V1\TenantCategoryController;
use App\Http\Controllers\Api\V1\TenantContentLikeController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\TenantDocumentController;
use App\Http\Controllers\Api\V1\TenantEventController;
use App\Http\Controllers\Api\V1\TenantEventLikeController;
use App\Http\Controllers\Api\V1\TenantOrderController;
use App\Http\Controllers\Api\V1\TenantOrganizationFollowController;
use App\Http\Controllers\Api\V1\TenantReceiptController;
use App\Http\Controllers\Api\V1\TenantRefundRequestController;
use App\Http\Controllers\Api\V1\TenantSettingController;
use App\Http\Controllers\Api\V1\TenantTagController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/me', [TenantAuthController::class, 'me'])
            ->middleware('ability:profile.read');
        Route::put('/me', [TenantAuthController::class, 'updateMe'])
            ->middleware('ability:profile.write');
        Route::post('/email/verification-notification', [TenantAuthController::class, 'sendVerificationNotification'])
            ->middleware('throttle:tenant-auth');
        Route::get('/avatar', [TenantAuthController::class, 'avatar'])
            ->middleware('ability:profile.read');
        Route::post('/avatar', [TenantAuthController::class, 'updateAvatar'])
            ->middleware('ability:profile.write');
        Route::put('/password', [TenantAuthController::class, 'updatePassword'])
            ->middleware('ability:profile.write');
        Route::get('/notifications', [TenantAuthController::class, 'notifications'])
            ->middleware('ability:notifications.read');
        Route::patch('/notifications/read-all', [TenantAuthController::class, 'markAllNotificationsAsRead'])
            ->middleware('ability:notifications.write');
        Route::patch('/notifications/{notification}/read', [TenantAuthController::class, 'markNotificationAsRead'])
            ->middleware('ability:notifications.write');
    });
});

Route::middleware(['initialize.tenant.route', 'auth.tenant.api'])->prefix('tenants/{tenant}')->group(function (): void {
    Route::get('/categories', TenantCategoryController::class);
    Route::get('/context', TenantContextController::class);
    Route::get('/organization-profile', [OrganizationProfileController::class, 'show']);
    Route::put('/organization-profile', [OrganizationProfileController::class, 'upsert']);
    Route::get('/organization-profile/follow', [TenantOrganizationFollowController::class, 'show']);
    Route::post('/organization-profile/follow', [TenantOrganizationFollowController::class, 'store'])
        ->middleware('throttle:tenant-engagement');
    Route::delete('/organization-profile/follow', [TenantOrganizationFollowController::class, 'destroy'])
        ->middleware('throttle:tenant-engagement');
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
        ->middleware('throttle:tenant-engagement');
    Route::delete('/events/{event}/like', [TenantEventLikeController::class, 'destroy'])
        ->middleware('throttle:tenant-engagement');

    Route::get('/content/likes', [TenantContentLikeController::class, 'index']);
    Route::get('/content/{module}/{content}/like', [TenantContentLikeController::class, 'show']);
    Route::post('/content/{module}/{content}/like', [TenantContentLikeController::class, 'store'])
        ->middleware('throttle:tenant-engagement');
    Route::delete('/content/{module}/{content}/like', [TenantContentLikeController::class, 'destroy'])
        ->middleware('throttle:tenant-engagement');

    Route::get('/orders', [TenantOrderController::class, 'index'])
        ->middleware('ability:orders.read');
    Route::get('/orders/{order}', [TenantOrderController::class, 'show'])
        ->middleware('ability:orders.read');
    Route::post('/refund-requests', [TenantRefundRequestController::class, 'store'])
        ->middleware(['throttle:tenant-refund', 'ability:refunds.write']);

    Route::get('/receipts', [TenantReceiptController::class, 'index'])
        ->middleware('ability:receipts.read');
    Route::get('/receipts/{receipt}', [TenantReceiptController::class, 'show'])
        ->middleware('ability:receipts.read');

    Route::get('/access-passes', [TenantAccessPassController::class, 'index'])
        ->middleware('ability:passes.read');
    Route::get('/access-passes/{accessPass}', [TenantAccessPassController::class, 'show'])
        ->middleware('ability:passes.read');

    Route::prefix('access-passes/{accessPass}/checkin')->group(function (): void {
        // Preview requires the passes.scan ability (scanner or admin tokens only).
        Route::get('/preview', [TenantAccessPassCheckinController::class, 'preview'])
            ->middleware(['throttle:tenant-checkin', 'ability:passes.scan']);
        // Consume requires the passes.scan ability to prevent buyers from self-validating.
        Route::post('/consume', [TenantAccessPassCheckinController::class, 'consume'])
            ->middleware(['throttle:tenant-checkin', 'ability:passes.scan']);
        // Administrative actions require passes.manage (admin/owner tokens only).
        Route::post('/reset', [TenantAccessPassCheckinController::class, 'reset'])
            ->middleware(['throttle:tenant-checkin', 'ability:passes.manage']);
        Route::post('/revoke', [TenantAccessPassCheckinController::class, 'revoke'])
            ->middleware(['throttle:tenant-checkin', 'ability:passes.manage']);
        Route::post('/reactivate', [TenantAccessPassCheckinController::class, 'reactivate'])
            ->middleware(['throttle:tenant-checkin', 'ability:passes.manage']);
    });
});
