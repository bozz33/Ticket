<?php

use App\Http\Controllers\Api\V1\Mobile\MobileAccountApplicationController;
use App\Http\Controllers\Api\V1\Mobile\MobileAccountContributionController;
use App\Http\Controllers\Api\V1\Mobile\MobileAccountDashboardController;
use App\Http\Controllers\Api\V1\Mobile\MobileBootstrapController;
use App\Http\Controllers\Api\V1\Mobile\MobileDeviceController;
use App\Http\Controllers\Api\V1\Mobile\MobileOrganizerDashboardController;
use App\Http\Controllers\Api\V1\Mobile\MobileOrganizerParticipantController;
use App\Http\Controllers\Api\V1\Mobile\MobileOrganizerStatsController;
use App\Http\Controllers\Api\V1\Mobile\MobileSupportRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::get('/bootstrap', MobileBootstrapController::class);
});

Route::middleware(['initialize.tenant.route', 'auth.tenant.api'])
    ->prefix('tenants/{tenant}/mobile')
    ->group(function (): void {
        Route::get('/account/dashboard', MobileAccountDashboardController::class);
        Route::get('/account/applications', MobileAccountApplicationController::class);
        Route::get('/account/contributions', MobileAccountContributionController::class);
        Route::post('/devices', [MobileDeviceController::class, 'store'])
            ->middleware('throttle:tenant-auth');
        Route::delete('/devices/{device}', [MobileDeviceController::class, 'destroy'])
            ->middleware('throttle:tenant-auth');
        Route::get('/support/requests', [MobileSupportRequestController::class, 'index']);
        Route::post('/support/requests', [MobileSupportRequestController::class, 'store'])
            ->middleware('throttle:tenant-engagement');
        Route::get('/support/requests/{ticket}', [MobileSupportRequestController::class, 'show']);
        Route::get('/organizer/dashboard', MobileOrganizerDashboardController::class);
        Route::get('/organizer/stats', MobileOrganizerStatsController::class);
        Route::get('/organizer/events/{event}/participants', MobileOrganizerParticipantController::class);
    });
