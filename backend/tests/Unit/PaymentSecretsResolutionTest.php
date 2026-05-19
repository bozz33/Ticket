<?php

namespace Tests\Unit;

use App\Models\PaymentGateway;
use App\Services\Payments\PaymentGatewayCredentialResolver;
use Tests\TestCase;

class PaymentSecretsResolutionTest extends TestCase
{
    public function test_public_payment_service_prefers_environment_secret_over_gateway_secret(): void
    {
        config()->set('services.paystack.secret_key', 'env-secret-key');

        $gateway = new PaymentGateway([
            'code' => 'paystack',
            'secret_key' => 'database-secret-key',
        ]);

        $service = new PaymentGatewayCredentialResolver;

        $this->assertSame('env-secret-key', $service->secretKey($gateway));
    }

    public function test_public_payment_service_falls_back_to_gateway_secret(): void
    {
        config()->set('services.paystack.secret_key', null);

        $gateway = new PaymentGateway([
            'code' => 'paystack',
            'secret_key' => 'database-secret-key',
        ]);

        $service = new PaymentGatewayCredentialResolver;

        $this->assertSame('database-secret-key', $service->secretKey($gateway));
    }

    public function test_payment_webhook_service_prefers_environment_webhook_secret(): void
    {
        config()->set('services.paystack.webhook_secret', 'env-webhook-secret');
        config()->set('services.paystack.secret_key', 'env-secret-key');

        $gateway = new PaymentGateway([
            'code' => 'paystack',
            'secret_key' => 'database-secret-key',
            'webhook_secret' => 'database-webhook-secret',
        ]);

        $service = new PaymentGatewayCredentialResolver;

        $this->assertSame('env-webhook-secret', $service->webhookSecret($gateway));
    }

    public function test_payment_webhook_service_falls_back_to_gateway_values(): void
    {
        config()->set('services.paystack.webhook_secret', null);
        config()->set('services.paystack.secret_key', null);

        $gateway = new PaymentGateway([
            'code' => 'paystack',
            'secret_key' => 'database-secret-key',
            'webhook_secret' => 'database-webhook-secret',
        ]);

        $service = new PaymentGatewayCredentialResolver;

        $this->assertSame('database-webhook-secret', $service->webhookSecret($gateway));
    }
}
