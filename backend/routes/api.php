<?php

use App\Http\Controllers\Api\V1\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthCheckController::class);

    require __DIR__.'/api/public.php';
    require __DIR__.'/api/webhooks.php';
    require __DIR__.'/api/platform.php';
    require __DIR__.'/api/tenant.php';
    require __DIR__.'/api/mobile.php';
});
