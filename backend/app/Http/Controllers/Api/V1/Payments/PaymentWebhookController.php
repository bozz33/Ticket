<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Payments\Contracts\PaymentWebhookReceiver;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        PaymentWebhookReceiver $paymentWebhookReceiver,
    ): JsonResponse {
        $log = $paymentWebhookReceiver->receive($gateway, $request);

        return response()->json([
            'data' => $log,
        ], $log->response_code ?? 202);
    }
}
