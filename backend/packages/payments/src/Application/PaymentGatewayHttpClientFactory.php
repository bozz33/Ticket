<?php

namespace Ticket\Payments\Application;

use App\Models\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PaymentGatewayHttpClientFactory
{
    public function forGateway(PaymentGateway $gateway): PendingRequest
    {
        return match ($gateway->code) {
            'paystack' => $this->forPaystack(),
            default => Http::acceptJson(),
        };
    }

    public function forPaystack(): PendingRequest
    {
        $request = Http::acceptJson()
            ->connectTimeout((float) config('services.paystack.connect_timeout', 10))
            ->timeout((float) config('services.paystack.timeout', 20));

        $configuredProxy = trim((string) config('services.paystack.proxy', ''));
        $disableEnvironmentProxy = (bool) config('services.paystack.disable_env_proxy', true);
        $configuredCaInfo = trim((string) config('services.paystack.cainfo', ''));
        $verifyTls = (bool) config('services.paystack.verify_tls', true);

        $options = [];

        if ($configuredProxy !== '') {
            $options['proxy'] = $configuredProxy;
        } elseif ($disableEnvironmentProxy) {
            $options['proxy'] = '';
        }

        if ($configuredCaInfo !== '') {
            $options['verify'] = $configuredCaInfo;
        } elseif (! $verifyTls) {
            $options['verify'] = false;
        }

        return $options !== [] ? $request->withOptions($options) : $request;
    }
}
