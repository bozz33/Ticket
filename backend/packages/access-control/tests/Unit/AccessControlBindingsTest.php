<?php

namespace Ticket\AccessControl\Tests\Unit;

use Tests\TestCase;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;

class AccessControlBindingsTest extends TestCase
{
    public function test_access_control_contracts_are_bound(): void
    {
        $this->assertInstanceOf(AccessPassCheckinWorkflow::class, app(AccessPassCheckinWorkflow::class));
    }
}
