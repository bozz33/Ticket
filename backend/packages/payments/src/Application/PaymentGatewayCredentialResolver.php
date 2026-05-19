<?php

namespace Ticket\Payments\Application;

use App\Models\PaymentGateway;

class PaymentGatewayCredentialResolver
{
    public function publicKey(PaymentGateway $gateway): ?string
    {
        if ($gateway->code === 'paystack') {
            $public = config('services.paystack.public_key');

            if (filled($public)) {
                return (string) $public;
            }
        }

        return filled($gateway->public_key) ? (string) $gateway->public_key : null;
    }

    public function secretKey(PaymentGateway $gateway): ?string
    {
        if ($gateway->code === 'paystack') {
            $secret = config('services.paystack.secret_key');

            if (filled($secret)) {
                return (string) $secret;
            }
        }

        return filled($gateway->secret_key) ? (string) $gateway->secret_key : null;
    }

    public function webhookSecret(PaymentGateway $gateway): ?string
    {
        if ($gateway->code === 'paystack') {
            $webhookSecret = config('services.paystack.webhook_secret');

            if (filled($webhookSecret)) {
                return (string) $webhookSecret;
            }

            $secret = config('services.paystack.secret_key');

            if (filled($secret)) {
                return (string) $secret;
            }
        }

        if (filled($gateway->webhook_secret)) {
            return (string) $gateway->webhook_secret;
        }

        return filled($gateway->secret_key) ? (string) $gateway->secret_key : null;
    }

    public function assertPaystackKeyMatchesMode(PaymentGateway $gateway, string $secretKey): void
    {
        if ($gateway->code !== 'paystack') {
            return;
        }

        $mode = strtolower((string) $gateway->mode);

        if ($mode === 'test' && ! str_starts_with($secretKey, 'sk_test_')) {
            throw new \RuntimeException('La clé secrète Paystack ne correspond pas au mode test.');
        }

        if ($mode === 'live' && ! str_starts_with($secretKey, 'sk_live_')) {
            throw new \RuntimeException('La clé secrète Paystack ne correspond pas au mode live.');
        }
    }
}
