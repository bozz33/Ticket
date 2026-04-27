<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        PaymentWebhookService $paymentWebhookService,
    ): JsonResponse {
        $log = $paymentWebhookService->receive($gateway, $request);

        return response()->json([
            'data' => $log,
        ], $log->response_code ?? 202);
    }
}
