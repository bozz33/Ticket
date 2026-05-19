<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Models\PlatformSetting;

class FinancePolicyService
{
    public const SETTING_GROUP = 'finance';

    public const SETTING_KEY = 'finance_policy';

    public const POLICY_VERSION = '2026-05-global-finance-policy';

    public const CARD_FEE_REFUND_POLICY = 'non_refundable_except_technical_issue_or_duplicate_charge';

    public function current(): array
    {
        $setting = PlatformSetting::query()
            ->where('group', self::SETTING_GROUP)
            ->where('key', self::SETTING_KEY)
            ->first();

        return $this->normalize((array) ($setting?->value ?? []));
    }

    public function ensureSetting(): PlatformSetting
    {
        $policy = $this->current();

        return PlatformSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'group' => self::SETTING_GROUP,
                'value' => $policy,
                'type' => 'json',
                'is_public' => false,
            ],
        );
    }

    public function commissionRate(): float
    {
        return (float) $this->current()['commission_rate'];
    }

    public function cardFeePerTicket(): int
    {
        return (int) $this->current()['card_fee_per_ticket'];
    }

    public function cardFeeRefundable(?string $reasonCode = null): bool
    {
        return in_array((string) $reasonCode, ['duplicate_charge', 'technical_issue'], true);
    }

    public function isCardPayment(?string $paymentMethod): bool
    {
        return $this->normalizePaymentChannel($paymentMethod) === PaymentChannel::Card->value;
    }

    public function normalizePaymentChannel(?string $paymentMethod): ?string
    {
        $normalized = strtolower(trim((string) $paymentMethod));

        return match (true) {
            $normalized === '' => null,
            $normalized === 'card' => PaymentChannel::Card->value,
            str_contains($normalized, 'wave'),
            str_contains($normalized, 'orange'),
            str_contains($normalized, 'mtn'),
            str_contains($normalized, 'moov'),
            str_contains($normalized, 'mobile') => PaymentChannel::MobileMoney->value,
            str_contains($normalized, 'bank') || str_contains($normalized, 'transfer') => PaymentChannel::BankTransfer->value,
            str_contains($normalized, 'wallet') => PaymentChannel::Wallet->value,
            default => $normalized,
        };
    }

    public function buildPricingSnapshot(
        int $subtotal,
        int $quantity,
        string $currencyCode,
        string $module,
        ?string $paymentMethod = null,
        int $gatewayFeeAmount = 0,
    ): array {
        $policy = $this->current();
        $paymentChannel = $this->normalizePaymentChannel($paymentMethod);
        $commissionRate = (float) $policy['commission_rate'];
        $commissionAmount = (int) round($subtotal * $commissionRate / 100);
        $cardFeePerTicket = $paymentChannel === PaymentChannel::Card->value
            ? (int) $policy['card_fee_per_ticket']
            : 0;
        $cardFeeTotal = $cardFeePerTicket * max(0, $quantity);
        $customerFeeTotal = $cardFeeTotal;
        $organizerFeeTotal = $commissionAmount;
        $customerTotal = $subtotal + $customerFeeTotal;
        $organizerNet = max(0, $subtotal - $organizerFeeTotal);
        $gatewayFeeAmount = max(0, $gatewayFeeAmount);

        return [
            'subtotal' => $subtotal,
            'service_fee' => $customerFeeTotal,
            'service_fee_label' => $cardFeeTotal > 0 ? 'Frais carte' : 'Frais de service',
            'service_fee_hint' => $cardFeeTotal > 0
                ? 'Les frais carte sont non remboursables sauf erreur technique confirmée ou débit en doublon.'
                : null,
            'customer_fee_total' => $customerFeeTotal,
            'organizer_fee_total' => $organizerFeeTotal,
            'absorbed_fee_total' => $gatewayFeeAmount,
            'gateway_fee_amount' => $gatewayFeeAmount,
            'platform_fee_amount' => $commissionAmount,
            'tax_amount' => 0,
            'total_fee_amount' => $customerFeeTotal + $organizerFeeTotal,
            'total' => $customerTotal,
            'customer_total' => $customerTotal,
            'organizer_net' => $organizerNet,
            'currency' => strtoupper($currencyCode),
            'quantity' => max(1, $quantity),
            'country_code' => null,
            'payment_channel' => $paymentChannel,
            'module' => $module,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'card_fee_per_ticket' => $cardFeePerTicket,
            'card_fee_quantity' => $cardFeePerTicket > 0 ? max(1, $quantity) : 0,
            'card_fee_total' => $cardFeeTotal,
            'finance_policy_version' => self::POLICY_VERSION,
            'policy' => [
                'module' => $module,
                'mode' => 'global_finance_policy',
                'commission_rate' => $commissionRate,
                'flat_fee_amount' => $cardFeePerTicket,
                'charge_bearer' => 'organizer',
            ],
            'refund_policy' => [
                'card_fee' => self::CARD_FEE_REFUND_POLICY,
                'platform_fee_refund_behavior' => 'refundable',
                'gateway_fee_refund_behavior' => 'non_refundable',
            ],
            'breakdown' => [
                'platform_fee' => [
                    'base_amount' => $commissionAmount,
                    'tax_amount' => 0,
                    'total' => $commissionAmount,
                    'charge_bearer' => 'organizer',
                    'rule' => [
                        'type' => 'global_finance_policy',
                        'refund_behavior' => 'refundable',
                        'commission_rate' => $commissionRate,
                    ],
                ],
                'gateway_fee' => [
                    'base_amount' => $gatewayFeeAmount,
                    'tax_amount' => 0,
                    'total' => $gatewayFeeAmount,
                    'charge_bearer' => 'platform',
                    'rule' => [
                        'type' => 'internal_gateway_cost',
                        'refund_behavior' => 'non_refundable',
                    ],
                ],
                'customer_fee' => [
                    'base_amount' => $cardFeeTotal,
                    'tax_amount' => 0,
                    'total' => $cardFeeTotal,
                    'charge_bearer' => 'buyer',
                    'rule' => [
                        'type' => 'card_fee_per_ticket',
                        'refund_behavior' => $cardFeeTotal > 0 ? 'conditional' : 'refundable',
                        'card_fee_per_ticket' => $cardFeePerTicket,
                        'quantity' => $cardFeePerTicket > 0 ? max(1, $quantity) : 0,
                        'refund_policy' => self::CARD_FEE_REFUND_POLICY,
                    ],
                ],
                'totals' => [
                    'buyer_fee_total' => $customerFeeTotal,
                    'organizer_fee_total' => $organizerFeeTotal,
                    'platform_absorbed_total' => $gatewayFeeAmount,
                    'tax_total' => 0,
                    'fee_total' => $customerFeeTotal + $organizerFeeTotal,
                ],
            ],
        ];
    }

    public function applyGatewayFeeToSnapshot(array $pricingSnapshot, int $gatewayFeeAmount): array
    {
        $gatewayFeeAmount = max(0, $gatewayFeeAmount);
        $pricingSnapshot['gateway_fee_amount'] = $gatewayFeeAmount;
        $pricingSnapshot['absorbed_fee_total'] = $gatewayFeeAmount;
        $pricingSnapshot['breakdown']['gateway_fee'] = [
            'base_amount' => $gatewayFeeAmount,
            'tax_amount' => 0,
            'total' => $gatewayFeeAmount,
            'charge_bearer' => 'platform',
            'rule' => [
                'type' => 'internal_gateway_cost',
                'refund_behavior' => 'non_refundable',
            ],
        ];
        $pricingSnapshot['breakdown']['totals']['platform_absorbed_total'] = $gatewayFeeAmount;

        return $pricingSnapshot;
    }

    private function normalize(array $value): array
    {
        return [
            'commission_rate' => max(0, round((float) ($value['commission_rate'] ?? 0), 4)),
            'card_fee_per_ticket' => max(0, (int) ($value['card_fee_per_ticket'] ?? 0)),
        ];
    }
}
