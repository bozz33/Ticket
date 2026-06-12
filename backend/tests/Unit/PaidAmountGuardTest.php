<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\TestCase;
use Ticket\Payments\Domain\PaidAmountGuard;

class PaidAmountGuardTest extends TestCase
{
    public function test_exact_payment_passes(): void
    {
        PaidAmountGuard::assertNotUnderpaid(5000, 'XOF', 5000, 'XOF', 'PAY-1');

        $this->expectNotToPerformAssertions();
    }

    public function test_overpayment_is_allowed(): void
    {
        PaidAmountGuard::assertNotUnderpaid(5000, 'XOF', 6000, 'XOF', 'PAY-2');

        $this->expectNotToPerformAssertions();
    }

    public function test_zero_expected_amount_is_not_checked(): void
    {
        PaidAmountGuard::assertNotUnderpaid(0, 'XOF', 0, 'XOF', 'PAY-FREE');

        $this->expectNotToPerformAssertions();
    }

    public function test_currency_is_compared_case_insensitively(): void
    {
        PaidAmountGuard::assertNotUnderpaid(5000, 'xof', 5000, 'XOF', 'PAY-3');

        $this->expectNotToPerformAssertions();
    }

    public function test_underpayment_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('inférieur au montant attendu');

        PaidAmountGuard::assertNotUnderpaid(5000, 'XOF', 3000, 'XOF', 'PAY-4');
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('différente de la devise attendue');

        PaidAmountGuard::assertNotUnderpaid(5000, 'XOF', 5000, 'USD', 'PAY-5');
    }
}
