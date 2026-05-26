<?php

namespace Ticket\IdentityAccess\Tests\Unit;

use Tests\TestCase;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;

class IdentityAccessBindingsTest extends TestCase
{
    public function test_identity_access_token_contracts_are_bound(): void
    {
        $this->assertInstanceOf(PlatformTokenIssuer::class, app(PlatformTokenIssuer::class));
        $this->assertInstanceOf(TenantTokenIssuer::class, app(TenantTokenIssuer::class));
    }
}
