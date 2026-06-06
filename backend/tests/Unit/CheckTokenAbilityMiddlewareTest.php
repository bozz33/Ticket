<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckTokenAbility;
use App\Models\UserApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Verifies that the CheckTokenAbility middleware correctly enforces token abilities.
 */
class CheckTokenAbilityMiddlewareTest extends TestCase
{
    private CheckTokenAbility $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckTokenAbility;
    }

    public function test_request_without_token_attribute_returns_401(): void
    {
        $request = new Request;
        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]), 'passes.scan');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_token_without_required_ability_returns_403(): void
    {
        $token = new UserApiToken(['abilities' => ['profile.read', 'orders.read']]);
        $request = new Request;
        $request->attributes->set('tenant_api_token', $token);

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]), 'passes.scan');

        $this->assertSame(403, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('non autorisée', (string) ($data['message'] ?? ''));
    }

    public function test_token_with_required_ability_passes_through(): void
    {
        $token = new UserApiToken(['abilities' => ['passes.scan', 'passes.read']]);
        $request = new Request;
        $request->attributes->set('tenant_api_token', $token);

        $response = $this->middleware->handle(
            $request,
            fn () => new JsonResponse(['ok' => true], 200),
            'passes.scan',
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_wildcard_token_passes_any_ability(): void
    {
        $token = new UserApiToken(['abilities' => ['*']]);
        $request = new Request;
        $request->attributes->set('tenant_api_token', $token);

        $response = $this->middleware->handle(
            $request,
            fn () => new JsonResponse(['ok' => true], 200),
            'passes.manage',
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_multiple_abilities_all_required(): void
    {
        $token = new UserApiToken(['abilities' => ['passes.scan']]);
        $request = new Request;
        $request->attributes->set('tenant_api_token', $token);

        // Token has passes.scan but not passes.manage - should fail.
        $response = $this->middleware->handle(
            $request,
            fn () => new JsonResponse(['ok' => true], 200),
            'passes.scan',
            'passes.manage',
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
