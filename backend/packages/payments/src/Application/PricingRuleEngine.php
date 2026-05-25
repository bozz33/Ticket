<?php

namespace Ticket\Payments\Application;

use App\Enums\CommercialModule;
use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Enums\MonetizationMode;
use App\Enums\PaymentChannel;
use App\Enums\RefundFeeBehavior;
use App\Models\CallForProject;
use App\Models\CommercialPolicy;
use App\Models\CrowdfundingCampaign;
use App\Models\GatewayFeeRule;
use App\Models\Offer;
use App\Models\PaymentGateway;
use App\Models\PlatformFeeRule;
use App\Models\Stand;
use App\Models\Tenant;
use App\Models\Training;
use App\Services\FinancePolicyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Ticket\Payments\Domain\CheckoutItem;

class PricingRuleEngine
{
    public function __construct(
        private readonly FinancePolicyService $financePolicyService,
    ) {}

    public function quote(
        Tenant $tenant,
        Offer $offer,
        int $quantity,
        ?PaymentGateway $gateway = null,
        ?string $paymentMethod = null,
    ): array {
        $subtotal = max(0, (int) $offer->price_amount) * max(1, $quantity);
        $currencyCode = strtoupper((string) ($offer->currency_code ?: $tenant->currency_code ?: 'XOF'));
        $countryCode = strtoupper((string) ($tenant->country_code ?: 'CI'));
        $module = $this->moduleFromOffer($offer);
        $paymentChannel = $this->normalizePaymentChannel($paymentMethod);

        if ($subtotal === 0) {
            return $this->buildFreeQuote($currencyCode, $quantity, $module);
        }

        $pricing = $this->financePolicyService->buildPricingSnapshot(
            subtotal: $subtotal,
            quantity: $quantity,
            currencyCode: $currencyCode,
            module: $module->value,
            paymentMethod: $paymentMethod,
        );

        $pricing['country_code'] = $countryCode;
        $pricing['payment_channel'] = $paymentChannel;

        return $pricing;
    }

    public function quoteCheckoutItem(
        Tenant $tenant,
        CheckoutItem $item,
        int $quantity,
        ?PaymentGateway $gateway = null,
        ?string $paymentMethod = null,
    ): array {
        if ($item->pricingOffer instanceof Offer) {
            return $this->quote($tenant, $item->pricingOffer, $quantity, $gateway, $paymentMethod);
        }

        $subtotal = max(0, $item->unitAmount) * max(1, $quantity);
        $currencyCode = strtoupper((string) ($item->currencyCode ?: $tenant->currency_code ?: 'XOF'));
        $countryCode = strtoupper((string) ($tenant->country_code ?: 'CI'));
        $module = CommercialModule::Ticketing;
        $paymentChannel = $this->normalizePaymentChannel($paymentMethod);

        if ($subtotal === 0) {
            return $this->buildFreeQuote($currencyCode, $quantity, $module);
        }

        $pricing = $this->financePolicyService->buildPricingSnapshot(
            subtotal: $subtotal,
            quantity: $quantity,
            currencyCode: $currencyCode,
            module: $module->value,
            paymentMethod: $paymentMethod,
        );

        $pricing['country_code'] = $countryCode;
        $pricing['payment_channel'] = $paymentChannel;

        return $pricing;
    }

    public function quoteCustomAmount(
        Tenant $tenant,
        int $subtotal,
        string $currencyCode,
        CommercialModule $module,
        ?string $paymentMethod = null,
    ): array {
        $subtotal = max(1, $subtotal);
        $currencyCode = strtoupper((string) ($currencyCode ?: $tenant->currency_code ?: 'XOF'));
        $countryCode = strtoupper((string) ($tenant->country_code ?: 'CI'));
        $paymentChannel = $this->normalizePaymentChannel($paymentMethod);

        $pricing = $this->financePolicyService->buildPricingSnapshot(
            subtotal: $subtotal,
            quantity: 1,
            currencyCode: $currencyCode,
            module: $module->value,
            paymentMethod: $paymentMethod,
        );

        $pricing['country_code'] = $countryCode;
        $pricing['payment_channel'] = $paymentChannel;

        return $pricing;
    }

    public function resolveGatewayForCurrency(string $currencyCode, ?string $paymentMethod = null): ?PaymentGateway
    {
        $channel = $this->normalizePaymentChannel($paymentMethod);

        return PaymentGateway::query()
            ->where('is_active', true)
            ->where(function ($query) use ($currencyCode): void {
                $query->whereNull('supported_currencies')
                    ->orWhereJsonContains('supported_currencies', strtoupper($currencyCode));
            })
            ->when($channel !== null, function ($query) use ($channel): void {
                $query->where(function ($nestedQuery) use ($channel): void {
                    $nestedQuery->whereNull('supported_channels')
                        ->orWhereJsonContains('supported_channels', $channel);
                });
            })
            ->orderByRaw("CASE WHEN code = 'paystack' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->first();
    }

    public function normalizePaymentChannel(?string $paymentMethod): string
    {
        $normalized = strtolower(trim((string) $paymentMethod));

        return match (true) {
            $normalized === '',
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

    private function buildFreeQuote(string $currencyCode, int $quantity, CommercialModule $module): array
    {
        $pricing = $this->financePolicyService->buildPricingSnapshot(
            subtotal: 0,
            quantity: $quantity,
            currencyCode: $currencyCode,
            module: $module->value,
        );

        $pricing['country_code'] = null;
        $pricing['payment_channel'] = null;

        return $pricing;
    }

    private function evaluateGatewayFeeRule(int $buyerBaseBeforeGateway, int $subtotal, ?array $rule): array
    {
        if ($rule === null) {
            return $this->emptyFee();
        }

        if (
            ($rule['charge_bearer'] ?? null) === FeeChargeBearer::Buyer->value
            && ($rule['is_gross_up_enabled'] ?? false) === true
        ) {
            return $this->evaluateGrossUpFeeRule(
                baseAmount: $buyerBaseBeforeGateway,
                feeMode: $rule['fee_mode'],
                percentageRate: $rule['percentage_rate'],
                fixedAmount: $rule['fixed_amount'],
                capAmount: $rule['cap_amount'],
                vatRate: $rule['vat_rate'],
            );
        }

        return $this->evaluateFeeRule(
            baseAmount: $subtotal,
            feeMode: $rule['fee_mode'],
            percentageRate: $rule['percentage_rate'],
            fixedAmount: $rule['fixed_amount'],
            capAmount: $rule['cap_amount'],
            vatRate: $rule['vat_rate'],
        );
    }

    private function evaluateFeeRule(
        int $baseAmount,
        ?string $feeMode,
        ?float $percentageRate,
        ?int $fixedAmount,
        ?int $capAmount,
        ?float $vatRate,
    ): array {
        $mode = $feeMode ?: FeeCalculationMode::Fixed->value;
        $percentageRate = max(0, (float) ($percentageRate ?? 0));
        $fixedAmount = max(0, (int) ($fixedAmount ?? 0));
        $capAmount = $capAmount !== null ? max(0, (int) $capAmount) : null;
        $vatRate = max(0, (float) ($vatRate ?? 0));

        $baseFeeAmount = match ($mode) {
            FeeCalculationMode::Percentage->value => (int) round($baseAmount * $percentageRate / 100),
            FeeCalculationMode::PercentagePlusFixed->value => (int) round($baseAmount * $percentageRate / 100) + $fixedAmount,
            default => $fixedAmount,
        };

        if ($capAmount !== null && $capAmount > 0) {
            $baseFeeAmount = min($baseFeeAmount, $capAmount);
        }

        $taxAmount = $vatRate > 0
            ? (int) round($baseFeeAmount * $vatRate / 100)
            : 0;

        return [
            'base_amount' => $baseFeeAmount,
            'tax_amount' => $taxAmount,
            'total' => $baseFeeAmount + $taxAmount,
        ];
    }

    private function evaluateGrossUpFeeRule(
        int $baseAmount,
        ?string $feeMode,
        ?float $percentageRate,
        ?int $fixedAmount,
        ?int $capAmount,
        ?float $vatRate,
    ): array {
        $mode = $feeMode ?: FeeCalculationMode::Fixed->value;
        $percentageRate = max(0, (float) ($percentageRate ?? 0));
        $fixedAmount = max(0, (int) ($fixedAmount ?? 0));
        $capAmount = $capAmount !== null ? max(0, (int) $capAmount) : null;
        $vatRate = max(0, (float) ($vatRate ?? 0));
        $taxMultiplier = 1 + ($vatRate / 100);
        $effectivePercentage = ($percentageRate / 100) * $taxMultiplier;
        $effectiveFixed = (int) round($fixedAmount * $taxMultiplier);

        $feeTotal = match ($mode) {
            FeeCalculationMode::Percentage->value,
            FeeCalculationMode::PercentagePlusFixed->value => $effectivePercentage >= 1
                ? 0
                : (int) ceil(($baseAmount * $effectivePercentage + ($mode === FeeCalculationMode::PercentagePlusFixed->value ? $effectiveFixed : 0)) / max(0.0001, 1 - $effectivePercentage)),
            default => $effectiveFixed,
        };

        if ($capAmount !== null && $capAmount > 0) {
            $grossCap = $vatRate > 0 ? (int) round($capAmount * $taxMultiplier) : $capAmount;
            $feeTotal = min($feeTotal, $grossCap);
        }

        $baseFeeAmount = $vatRate > 0
            ? (int) round($feeTotal / $taxMultiplier)
            : $feeTotal;
        $taxAmount = max(0, $feeTotal - $baseFeeAmount);

        return [
            'base_amount' => $baseFeeAmount,
            'tax_amount' => $taxAmount,
            'total' => $feeTotal,
        ];
    }

    private function emptyFee(): array
    {
        return [
            'base_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ];
    }

    private function resolveGatewayFeeRule(
        PaymentGateway $gateway,
        string $countryCode,
        string $currencyCode,
        string $paymentChannel,
    ): ?array {
        $rules = GatewayFeeRule::query()
            ->where('payment_gateway_id', $gateway->getKey())
            ->where('is_active', true)
            ->where(function ($query) use ($countryCode): void {
                $query->whereNull('country_code')->orWhere('country_code', $countryCode);
            })
            ->where(function ($query) use ($currencyCode): void {
                $query->whereNull('currency_code')->orWhere('currency_code', $currencyCode);
            })
            ->where(function ($query) use ($paymentChannel): void {
                $query->whereNull('payment_channel')->orWhere('payment_channel', $paymentChannel);
            })
            ->get();

        return $this->pickBestRule(
            $rules,
            [
                'country_code' => $countryCode,
                'currency_code' => $currencyCode,
                'payment_channel' => $paymentChannel,
            ],
        );
    }

    private function resolvePlatformFeeRule(
        Tenant $tenant,
        CommercialModule $module,
        string $countryCode,
        string $currencyCode,
    ): ?array {
        $rules = PlatformFeeRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($tenant): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->getKey());
            })
            ->where(function ($query) use ($module): void {
                $query->whereNull('module')->orWhere('module', $module->value);
            })
            ->where(function ($query) use ($countryCode): void {
                $query->whereNull('country_code')->orWhere('country_code', $countryCode);
            })
            ->where(function ($query) use ($currencyCode): void {
                $query->whereNull('currency_code')->orWhere('currency_code', $currencyCode);
            })
            ->get();

        $matchedRule = $this->pickBestRule(
            $rules,
            [
                'tenant_id' => $tenant->getKey(),
                'module' => $module->value,
                'country_code' => $countryCode,
                'currency_code' => $currencyCode,
            ],
        );

        if ($matchedRule !== null) {
            return $matchedRule;
        }

        return $this->legacyCommercialPolicyRule($module);
    }

    private function pickBestRule(Collection $rules, array $context): ?array
    {
        $now = now();

        return $rules
            ->filter(function ($rule): bool {
                $startsAt = $rule->effective_from ? Carbon::parse($rule->effective_from) : null;
                $endsAt = $rule->effective_to ? Carbon::parse($rule->effective_to) : null;

                if ($startsAt && $startsAt->isFuture()) {
                    return false;
                }

                if ($endsAt && $endsAt->isPast()) {
                    return false;
                }

                return true;
            })
            ->sort(function ($left, $right) use ($context): int {
                $leftScore = $this->ruleSpecificityScore($left, $context);
                $rightScore = $this->ruleSpecificityScore($right, $context);

                if ($leftScore !== $rightScore) {
                    return $rightScore <=> $leftScore;
                }

                $leftPriority = (int) ($left->priority ?? 100);
                $rightPriority = (int) ($right->priority ?? 100);

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                return $right->getKey() <=> $left->getKey();
            })
            ->map(fn ($rule): array => [
                'id' => $rule->getKey(),
                'public_id' => $rule->public_id ?? null,
                'name' => $rule->name ?? null,
                'charge_bearer' => $rule->charge_bearer?->value ?? $rule->charge_bearer,
                'fee_mode' => $rule->fee_mode?->value ?? $rule->fee_mode,
                'percentage_rate' => (float) ($rule->percentage_rate ?? 0),
                'fixed_amount' => (int) ($rule->fixed_amount ?? 0),
                'cap_amount' => $rule->cap_amount !== null ? (int) $rule->cap_amount : null,
                'vat_rate' => (float) ($rule->vat_rate ?? 0),
                'refund_behavior' => $rule->refund_behavior?->value ?? $rule->refund_behavior ?? RefundFeeBehavior::Refundable->value,
                'is_gross_up_enabled' => (bool) ($rule->is_gross_up_enabled ?? false),
                'meta' => is_array($rule->meta ?? null) ? $rule->meta : null,
                'country_code' => $rule->country_code ?? null,
                'currency_code' => $rule->currency_code ?? null,
                'payment_channel' => $rule->payment_channel?->value ?? $rule->payment_channel ?? null,
                'module' => $rule->module?->value ?? $rule->module ?? null,
            ])
            ->first();
    }

    private function ruleSpecificityScore(object $rule, array $context): int
    {
        $score = 0;

        foreach ($context as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (($rule->{$field} ?? null) === $value) {
                $score += 10;
            } elseif (($rule->{$field} ?? null) === null) {
                $score += 1;
            }
        }

        return $score;
    }

    private function legacyCommercialPolicyRule(CommercialModule $module): ?array
    {
        $policy = CommercialPolicy::query()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->first();

        if (! $policy) {
            return null;
        }

        $commissionRate = (float) ($policy->commission_rate ?? 0);
        $fixedAmount = (int) ($policy->flat_fee_amount ?? 0);
        $mode = $policy->monetization_mode?->value ?? $policy->monetization_mode;

        if ($mode === MonetizationMode::Free->value) {
            return null;
        }

        return [
            'id' => $policy->getKey(),
            'public_id' => null,
            'name' => 'Legacy commercial policy',
            'charge_bearer' => FeeChargeBearer::Buyer->value,
            'fee_mode' => $mode === MonetizationMode::Hybrid->value
                ? FeeCalculationMode::PercentagePlusFixed->value
                : ($fixedAmount > 0 && $commissionRate > 0
                    ? FeeCalculationMode::PercentagePlusFixed->value
                    : ($commissionRate > 0 ? FeeCalculationMode::Percentage->value : FeeCalculationMode::Fixed->value)),
            'percentage_rate' => $commissionRate,
            'fixed_amount' => $fixedAmount,
            'cap_amount' => null,
            'vat_rate' => 0,
            'refund_behavior' => RefundFeeBehavior::Refundable->value,
            'is_gross_up_enabled' => false,
            'meta' => ['legacy_policy_id' => $policy->getKey()],
            'country_code' => null,
            'currency_code' => strtoupper((string) ($policy->currency_code ?: 'XOF')),
            'payment_channel' => null,
            'module' => $module->value,
        ];
    }

    private function ruleSummary(?array $rule): ?array
    {
        if ($rule === null) {
            return null;
        }

        return [
            'id' => $rule['id'] ?? null,
            'public_id' => $rule['public_id'] ?? null,
            'name' => $rule['name'] ?? null,
            'charge_bearer' => $rule['charge_bearer'] ?? null,
            'fee_mode' => $rule['fee_mode'] ?? null,
            'percentage_rate' => $rule['percentage_rate'] ?? null,
            'fixed_amount' => $rule['fixed_amount'] ?? null,
            'cap_amount' => $rule['cap_amount'] ?? null,
            'vat_rate' => $rule['vat_rate'] ?? null,
            'refund_behavior' => $rule['refund_behavior'] ?? null,
            'country_code' => $rule['country_code'] ?? null,
            'currency_code' => $rule['currency_code'] ?? null,
            'payment_channel' => $rule['payment_channel'] ?? null,
            'module' => $rule['module'] ?? null,
        ];
    }

    private function legacyPolicySummary(?array $rule): ?array
    {
        if ($rule === null) {
            return null;
        }

        return [
            'module' => $rule['module'] ?? null,
            'mode' => $rule['fee_mode'] ?? null,
            'commission_rate' => (float) ($rule['percentage_rate'] ?? 0),
            'flat_fee_amount' => (int) ($rule['fixed_amount'] ?? 0),
            'charge_bearer' => $rule['charge_bearer'] ?? null,
        ];
    }

    private function moduleFromOffer(Offer $offer): CommercialModule
    {
        return match ($offer->offerable_type) {
            CallForProject::class => CommercialModule::CallsForProjects,
            Training::class => CommercialModule::Training,
            Stand::class => CommercialModule::Stands,
            CrowdfundingCampaign::class => CommercialModule::Crowdfunding,
            default => CommercialModule::Ticketing,
        };
    }
}
