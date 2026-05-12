<?php

namespace Tests\Unit;

use App\Services\Payments\PaymentGatewayHttpClientFactory;
use Illuminate\Http\Client\PendingRequest;
use ReflectionClass;
use Tests\TestCase;

class PaymentGatewayHttpClientFactoryTest extends TestCase
{
    public function test_paystack_client_disables_environment_proxy_by_default(): void
    {
        config()->set('services.paystack.proxy', null);
        config()->set('services.paystack.disable_env_proxy', true);
        config()->set('services.paystack.connect_timeout', 11);
        config()->set('services.paystack.timeout', 21);

        $request = app(PaymentGatewayHttpClientFactory::class)->forPaystack();

        $options = $this->extractOptions($request);

        $this->assertSame('', $options['proxy'] ?? null);
        $this->assertSame(11.0, $options['connect_timeout'] ?? null);
        $this->assertSame(21.0, $options['timeout'] ?? null);
    }

    public function test_paystack_client_uses_explicit_proxy_when_configured(): void
    {
        config()->set('services.paystack.proxy', 'http://proxy.example.test:8080');
        config()->set('services.paystack.disable_env_proxy', true);

        $request = app(PaymentGatewayHttpClientFactory::class)->forPaystack();

        $options = $this->extractOptions($request);

        $this->assertSame('http://proxy.example.test:8080', $options['proxy'] ?? null);
    }

    public function test_paystack_client_can_disable_tls_verification_for_local_debugging(): void
    {
        config()->set('services.paystack.proxy', null);
        config()->set('services.paystack.disable_env_proxy', true);
        config()->set('services.paystack.cainfo', null);
        config()->set('services.paystack.verify_tls', false);

        $request = app(PaymentGatewayHttpClientFactory::class)->forPaystack();

        $options = $this->extractOptions($request);

        $this->assertFalse($options['verify'] ?? true);
    }

    public function test_paystack_client_prefers_explicit_ca_bundle_when_provided(): void
    {
        config()->set('services.paystack.proxy', null);
        config()->set('services.paystack.disable_env_proxy', true);
        config()->set('services.paystack.cainfo', 'C:/certs/custom-ca.pem');
        config()->set('services.paystack.verify_tls', true);

        $request = app(PaymentGatewayHttpClientFactory::class)->forPaystack();

        $options = $this->extractOptions($request);

        $this->assertSame('C:/certs/custom-ca.pem', $options['verify'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractOptions(PendingRequest $request): array
    {
        $reflection = new ReflectionClass($request);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);

        return (array) $property->getValue($request);
    }
}
