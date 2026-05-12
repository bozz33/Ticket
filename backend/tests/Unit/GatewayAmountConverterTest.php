<?php

namespace Tests\Unit;

use App\Support\Payments\GatewayAmountConverter;
use Tests\TestCase;

class GatewayAmountConverterTest extends TestCase
{
    public function test_paystack_uses_lowest_subunit_for_xof(): void
    {
        $converter = app(GatewayAmountConverter::class);

        $this->assertSame(1585000, $converter->toGateway(15850, 'XOF', 'paystack'));
        $this->assertSame(15850, $converter->fromGateway(1585000, 'XOF', 'paystack'));
    }

    public function test_unknown_gateway_keeps_internal_amount_as_is(): void
    {
        $converter = app(GatewayAmountConverter::class);

        $this->assertSame(15850, $converter->toGateway(15850, 'XOF', 'internal'));
        $this->assertSame(15850, $converter->fromGateway(15850, 'XOF', 'internal'));
    }
}
