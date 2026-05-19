<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\GatewayWebhookLog;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Ticket\Payments\Application\PaymentWebhookService;
use Ticket\Payments\Contracts\PaymentWebhookReceiver;

class LaravelPaymentWebhookReceiver implements PaymentWebhookReceiver
{
    public function __construct(
        private readonly PaymentWebhookService $webhooks,
    ) {}

    public function receive(PaymentGateway $gateway, Request $request): GatewayWebhookLog
    {
        return $this->webhooks->receive($gateway, $request);
    }
}
