<?php

namespace Ticket\Payments\Contracts;

use App\Models\GatewayWebhookLog;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

interface PaymentWebhookReceiver
{
    public function receive(PaymentGateway $gateway, Request $request): GatewayWebhookLog;
}
