<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Auth\TenantAuthController;
use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Foundation\Testing\WithFaker;
use ReflectionClass;
use Tests\TestCase;

/**
 * Verifies that TenantAuthController assigns the correct token abilities
 * based on the authenticated user's role.
 */
class TenantTokenAbilitiesTest extends TestCase
{
    use WithFaker;

    public function test_buyer_user_receives_buyer_abilities_not_wildcard(): void
    {
        $token = new UserApiToken(['abilities' => $this->getBuyerAbilities()]);

        $this->assertTrue($token->can('profile.read'));
        $this->assertTrue($token->can('orders.read'));
        $this->assertTrue($token->can('receipts.read'));
        $this->assertTrue($token->can('passes.read'));
        $this->assertTrue($token->can('notifications.read'));
        $this->assertTrue($token->can('refunds.write'));

        $this->assertFalse($token->can('passes.scan'));
        $this->assertFalse($token->can('passes.manage'));
    }

    public function test_wildcard_token_allows_all_abilities(): void
    {
        $token = new UserApiToken(['abilities' => ['*']]);

        $this->assertTrue($token->can('passes.scan'));
        $this->assertTrue($token->can('passes.manage'));
        $this->assertTrue($token->can('anything.arbitrary'));
    }

    public function test_scanner_token_allows_scan_but_not_manage(): void
    {
        $token = new UserApiToken(['abilities' => ['passes.read', 'passes.scan']]);

        $this->assertTrue($token->can('passes.read'));
        $this->assertTrue($token->can('passes.scan'));

        $this->assertFalse($token->can('passes.manage'));
        $this->assertFalse($token->can('orders.read'));
    }

    public function test_token_with_null_abilities_allows_everything(): void
    {
        $token = new UserApiToken(['abilities' => null]);

        // Null abilities means no restriction (legacy behavior).
        $this->assertTrue($token->can('passes.manage'));
        $this->assertTrue($token->can('anything'));
    }

    public function test_resolve_abilities_method_is_private_and_role_based(): void
    {
        $reflection = new ReflectionClass(TenantAuthController::class);

        $method = $reflection->getMethod('resolveAbilitiesForUser');
        $this->assertTrue($method->isPrivate(), 'resolveAbilitiesForUser must be private');

        $constants = $reflection->getConstants();
        $this->assertArrayHasKey('BUYER_ABILITIES', $constants);
        $this->assertArrayHasKey('SCANNER_ABILITIES', $constants);
        $this->assertIsArray($constants['BUYER_ABILITIES']);
        $this->assertNotContains('passes.scan', $constants['BUYER_ABILITIES']);
        $this->assertNotContains('passes.manage', $constants['BUYER_ABILITIES']);
    }

    private function getBuyerAbilities(): array
    {
        $reflection = new ReflectionClass(TenantAuthController::class);

        return $reflection->getConstant('BUYER_ABILITIES');
    }
}
