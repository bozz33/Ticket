<?php

namespace Ticket\FinanceAccounting\Contracts;

use App\Models\PlatformSetting;

interface FinancePolicyCatalog
{
    public const SETTING_GROUP = 'finance';

    public const SETTING_KEY = 'finance_policy';

    public const POLICY_VERSION = '2026-05-global-finance-policy';

    public const CARD_FEE_REFUND_POLICY = 'non_refundable_except_technical_issue_or_duplicate_charge';

    public function current(): array;

    public function ensureSetting(): PlatformSetting;

    public function commissionRate(): float;

    public function cardFeePerTicket(): int;

    public function cardFeeRefundable(?string $reasonCode = null): bool;

    public function isCardPayment(?string $paymentMethod): bool;

    public function normalizePaymentChannel(?string $paymentMethod): ?string;

    public function buildPricingSnapshot(
        int $subtotal,
        int $quantity,
        string $currencyCode,
        string $module,
        ?string $paymentMethod = null,
        int $gatewayFeeAmount = 0,
    ): array;

    public function applyGatewayFeeToSnapshot(array $pricingSnapshot, int $gatewayFeeAmount): array;
}
