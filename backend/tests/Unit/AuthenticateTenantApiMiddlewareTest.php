<?php

namespace Tests\Unit;

use App\Http\Middleware\AuthenticateTenantApi;
use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;

/**
 * Verifies that AuthenticateTenantApi rejects inactive accounts and missing tokens.
 */
class AuthenticateTenantApiMiddlewareTest extends TestCase
{
    private TenantTokenIssuer|MockInterface $tokenService;

    private AuthenticateTenantApi $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = Mockery::mock(TenantTokenIssuer::class);
        $this->middleware = new AuthenticateTenantApi($this->tokenService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_missing_bearer_returns_401(): void
    {
        $request = new Request;
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('manquant', (string) ($data['message'] ?? ''));
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->tokenService->shouldReceive('findToken')->once()->andReturn(null);

        $request = $this->requestWithBearer('invalid-token');
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_inactive_user_is_rejected_with_401(): void
    {
        $user = new User;
        $user->forceFill(['is_active' => false]);

        $token = new UserApiToken;
        $token->setRelation('user', $user);

        $this->tokenService->shouldReceive('findToken')->once()->andReturn($token);

        $request = $this->requestWithBearer('valid-token');
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('Compte désactivé', (string) ($data['message'] ?? ''));
    }

    public function test_null_user_is_rejected_with_401(): void
    {
        $token = new UserApiToken;
        $token->setRelation('user', null);

        $this->tokenService->shouldReceive('findToken')->once()->andReturn($token);

        $request = $this->requestWithBearer('valid-token');
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_active_user_sets_request_attributes_and_passes_through(): void
    {
        $user = new User;
        $user->forceFill(['is_active' => true]);

        // Use a partial mock so touchLastUsed() can be intercepted without a DB call.
        $token = Mockery::mock(UserApiToken::class)->makePartial();
        $token->setRelation('user', $user);
        $token->shouldReceive('touchLastUsed')->once();

        $this->tokenService->shouldReceive('findToken')->once()->andReturn($token);

        $request = $this->requestWithBearer('valid-token');
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true], 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user, $request->attributes->get('tenant_user'));
        $this->assertSame($token, $request->attributes->get('tenant_api_token'));
    }

    private function requestWithBearer(string $token): Request
    {
        $request = new Request;
        $request->headers->set('Authorization', "Bearer {$token}");

        return $request;
    }
}
