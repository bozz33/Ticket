<?php

use App\Http\Controllers\Api\V1\Payments\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/webhooks/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:payment-webhooks');
