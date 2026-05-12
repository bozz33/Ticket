<?php

namespace Tests\Unit;

use App\Models\PlatformTransaction;
use App\Services\Payments\RefundService;
use Tests\TestCase;

class RefundServiceQuoteTest extends TestCase
{
    public function test_quote_computes_customer_refund_and_organizer_reversal_from_snapshot(): void
    {
        $transaction = new PlatformTransaction([
            'transaction_reference' => 'PAY-QUOTE-001',
            'gross_amount' => 11200,
            'net_amount' => 9800,
            'gateway_fee_amount' => 200,
            'platform_fee_amount' => 1000,
            'tax_amount' => 200,
            'customer_fee_amount' => 1200,
            'currency_code' => 'XOF',
            'pricing_snapshot' => [
                'subtotal' => 10000,
                'breakdown' => [
                    'platform_fee' => [
                        'base_amount' => 1000,
                        'tax_amount' => 200,
                        'total' => 1200,
                        'charge_bearer' => 'buyer',
                        'rule' => [
                            'refund_behavior' => 'refundable',
                        ],
                    ],
                    'gateway_fee' => [
                        'base_amount' => 200,
                        'tax_amount' => 0,
                        'total' => 200,
                        'charge_bearer' => 'organizer',
                        'rule' => [
                            'refund_behavior' => 'non_refundable',
                        ],
                    ],
                ],
            ],
        ]);

        $quote = app(RefundService::class)->quote($transaction);

        $this->assertSame(10000, $quote['subtotal_amount']);
        $this->assertSame(11200, $quote['customer_refund_amount']);
        $this->assertSame(9800, $quote['organizer_reversal_amount']);
        $this->assertSame(1400, $quote['platform_absorption_amount']);
        $this->assertSame(0, $quote['platform_fee_retained']);
        $this->assertSame(200, $quote['gateway_fee_retained']);
        $this->assertSame(1000, $quote['platform_fee_reversed']);
        $this->assertSame(0, $quote['gateway_fee_reversed']);
        $this->assertSame(200, $quote['tax_reversed']);
        $this->assertSame(1200, $quote['customer_fee_refunded']);
    }
}
