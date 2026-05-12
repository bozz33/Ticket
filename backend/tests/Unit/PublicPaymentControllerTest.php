<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Public\PublicPaymentController;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserApiToken;
use App\Services\Auth\TenantTokenService;
use App\Services\Payments\PublicPaymentService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class PublicPaymentControllerTest extends TestCase
{
    public function test_initialize_blocks_authenticated_buyer_when_account_is_not_ready(): void
    {
        $buyer = new User();
        $buyer->forceFill([
            'id' => 42,
            'name' => 'Buyer Test',
            'first_name' => null,
            'last_name' => null,
            'email' => 'buyer@example.test',
            'phone' => null,
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->forceFill([
            'id' => 7,
            'public_id' => 'tenant-public-id',
            'name' => 'Demo Tenant',
            'slug' => 'demo-tenant',
        ]);
        $tenant->shouldNotReceive('run');
        $tenant->shouldReceive('only')
            ->andReturn([
                'id' => 7,
                'public_id' => 'tenant-public-id',
                'name' => 'Demo Tenant',
                'slug' => 'demo-tenant',
            ]);

        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->once()->andReturn($tenant);

        $paymentService = Mockery::mock(PublicPaymentService::class);
        $paymentService->shouldNotReceive('initialize');

        $tokenService = Mockery::mock(TenantTokenService::class);
        $apiToken = Mockery::mock(UserApiToken::class)->makePartial();
        $apiToken->setRelation('user', $buyer);
        $apiToken->shouldReceive('touchLastUsed')->once();

        $tokenService->shouldReceive('findToken')
            ->once()
            ->with('plain-token')
            ->andReturn($apiToken);

        $controller = new PublicPaymentController($tenantContext, $paymentService, $tokenService, app(\App\Support\Buyers\BuyerAccountReadiness::class));

        $request = Request::create('/api/v1/public/tenants/demo-tenant/payments/initialize', 'POST', [
            'offer' => 'offer-public-id',
            'quantity' => 2,
            'content_module' => 'evenements',
            'content_slug' => 'summit-demo-paid-2026',
            'callback_url' => 'http://localhost:3000/checkout/evenements/summit-demo-paid-2026/succes?offer=offer-public-id',
        ]);
        $request->headers->set('Authorization', 'Bearer plain-token');

        $response = $controller->initialize($request, 'demo-tenant');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('ACCOUNT_NOT_READY', $response->getData(true)['code'] ?? null);
        $this->assertFalse($response->getData(true)['requirements']['email_verified'] ?? true);
        $this->assertFalse($response->getData(true)['requirements']['profile_completed'] ?? true);
        $this->assertContains('phone', $response->getData(true)['requirements']['missing_profile_fields'] ?? []);
    }

    public function test_initialize_allows_authenticated_buyer_when_account_is_ready(): void
    {
        $buyer = new User();
        $buyer->forceFill([
            'id' => 43,
            'name' => 'Buyer Ready',
            'first_name' => 'Buyer',
            'last_name' => 'Ready',
            'email' => 'ready@example.test',
            'phone' => '+2250700000000',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->forceFill([
            'id' => 8,
            'public_id' => 'tenant-public-id-2',
            'name' => 'Ready Tenant',
            'slug' => 'ready-tenant',
        ]);
        $tenant->shouldReceive('run')
            ->once()
            ->andReturnUsing(static fn (callable $callback) => $callback());
        $tenant->shouldReceive('only')
            ->andReturn([
                'id' => 8,
                'public_id' => 'tenant-public-id-2',
                'name' => 'Ready Tenant',
                'slug' => 'ready-tenant',
            ]);

        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->once()->andReturn($tenant);

        $capturedPayload = null;

        $paymentService = Mockery::mock(PublicPaymentService::class);
        $paymentService->shouldReceive('initialize')
            ->once()
            ->with($tenant, Mockery::on(function (array $payload) use (&$capturedPayload): bool {
                $capturedPayload = $payload;

                return true;
            }))
            ->andReturn([
                'mode' => 'redirect',
                'reference' => 'PAY-XOF-260509-0710-ABC123',
                'authorization_url' => 'https://paystack.test/redirect',
            ]);

        $tokenService = Mockery::mock(TenantTokenService::class);
        $apiToken = Mockery::mock(UserApiToken::class)->makePartial();
        $apiToken->setRelation('user', $buyer);
        $apiToken->shouldReceive('touchLastUsed')->once();

        $tokenService->shouldReceive('findToken')
            ->once()
            ->with('plain-token')
            ->andReturn($apiToken);

        $controller = new PublicPaymentController($tenantContext, $paymentService, $tokenService, app(\App\Support\Buyers\BuyerAccountReadiness::class));

        $request = Request::create('/api/v1/public/tenants/ready-tenant/payments/initialize', 'POST', [
            'offer' => 'offer-public-id',
            'quantity' => 2,
            'content_module' => 'evenements',
            'content_slug' => 'summit-demo-paid-2026',
            'callback_url' => 'http://localhost:3000/checkout/evenements/summit-demo-paid-2026/succes?offer=offer-public-id',
        ]);
        $request->headers->set('Authorization', 'Bearer plain-token');

        $response = $controller->initialize($request, 'ready-tenant');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotNull($capturedPayload);
        $this->assertSame(43, $capturedPayload['buyer_user_id']);
        $this->assertSame('Buyer Ready', $capturedPayload['buyer_name']);
        $this->assertSame('ready@example.test', $capturedPayload['buyer_email']);
        $this->assertSame('+2250700000000', $capturedPayload['buyer_phone']);
        $this->assertSame('redirect', $response->getData(true)['data']['mode'] ?? null);
    }
}
