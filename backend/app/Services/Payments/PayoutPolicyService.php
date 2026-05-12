<?php

namespace App\Services\Payments;

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Models\PayoutPolicy;
use App\Models\PlatformTransaction;
use App\Models\Settlement;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class PayoutPolicyService
{
    public function resolvePolicy(Tenant $tenant, ?string $currencyCode = null): ?PayoutPolicy
    {
        $countryCode = strtoupper((string) ($tenant->country_code ?: ''));
        $currencyCode = strtoupper((string) ($currencyCode ?: $tenant->currency_code ?: 'XOF'));
        $now = now();

        return PayoutPolicy::query()
            ->where('is_active', true)
            ->where(function ($query) use ($tenant): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->getKey());
            })
            ->where(function ($query) use ($countryCode): void {
                $query->whereNull('country_code')->orWhere('country_code', $countryCode);
            })
            ->where(function ($query) use ($currencyCode): void {
                $query->whereNull('currency_code')->orWhere('currency_code', $currencyCode);
            })
            ->get()
            ->filter(function (PayoutPolicy $policy) use ($now): bool {
                $startsAt = $policy->effective_from ? Carbon::parse($policy->effective_from) : null;
                $endsAt = $policy->effective_to ? Carbon::parse($policy->effective_to) : null;

                if ($startsAt && $startsAt->isFuture()) {
                    return false;
                }

                if ($endsAt && $endsAt->isPast()) {
                    return false;
                }

                return true;
            })
            ->sort(function (PayoutPolicy $left, PayoutPolicy $right) use ($tenant, $countryCode, $currencyCode): int {
                $leftScore = $this->scorePolicy($left, $tenant->getKey(), $countryCode, $currencyCode);
                $rightScore = $this->scorePolicy($right, $tenant->getKey(), $countryCode, $currencyCode);

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
            ->first();
    }

    public function computePayout(Tenant $tenant, int $grossAmount, ?string $currencyCode = null): array
    {
        $policy = $this->resolvePolicy($tenant, $currencyCode);
        $grossAmount = max(0, $grossAmount);
        $currencyCode = strtoupper((string) ($currencyCode ?: $tenant->currency_code ?: 'XOF'));

        if (! $policy) {
            return [
                'gross_amount' => $grossAmount,
                'fee_amount' => 0,
                'net_amount' => $grossAmount,
                'currency_code' => $currencyCode,
                'minimum_payout_amount' => 1000,
                'reserve_rate' => 0,
                'reserve_days' => 0,
                'payout_delay_days' => 0,
                'requires_manual_review' => true,
                'auto_payout_enabled' => false,
                'charge_bearer' => FeeChargeBearer::Organizer->value,
                'policy' => null,
            ];
        }

        $feeAmount = $this->evaluateFee(
            amount: $grossAmount,
            mode: $policy->payout_fee_mode?->value ?? $policy->payout_fee_mode,
            percentageRate: (float) ($policy->payout_fee_percentage ?? 0),
            fixedAmount: (int) ($policy->payout_fee_fixed ?? 0),
            capAmount: $policy->payout_fee_cap !== null ? (int) $policy->payout_fee_cap : null,
        );

        $netAmount = match ($policy->charge_bearer?->value ?? $policy->charge_bearer) {
            FeeChargeBearer::Organizer->value => max(0, $grossAmount - $feeAmount),
            default => $grossAmount,
        };

        return [
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'currency_code' => $currencyCode,
            'minimum_payout_amount' => (int) ($policy->minimum_payout_amount ?? 0),
            'reserve_rate' => (float) ($policy->reserve_rate ?? 0),
            'reserve_days' => (int) ($policy->reserve_days ?? 0),
            'payout_delay_days' => (int) ($policy->payout_delay_days ?? 0),
            'requires_manual_review' => (bool) ($policy->requires_manual_review ?? false),
            'auto_payout_enabled' => (bool) ($policy->auto_payout_enabled ?? false),
            'charge_bearer' => $policy->charge_bearer?->value ?? $policy->charge_bearer,
            'policy' => [
                'id' => $policy->getKey(),
                'public_id' => $policy->public_id,
                'name' => $policy->name,
            ],
        ];
    }

    public function availableBalance(Tenant $tenant, ?string $currencyCode = null): array
    {
        $policy = $this->resolvePolicy($tenant, $currencyCode);
        $currencyCode = strtoupper((string) ($currencyCode ?: $tenant->currency_code ?: 'XOF'));
        $delayDays = (int) ($policy?->payout_delay_days ?? 0);
        $reserveRate = (float) ($policy?->reserve_rate ?? 0);
        $reserveDays = (int) ($policy?->reserve_days ?? 0);
        $maturedCutoff = now()->subDays($delayDays);
        $reserveCutoff = now()->subDays($reserveDays);
        $successfulStatuses = ['success', 'successful', 'confirmed', 'completed', 'paid'];

        $creditedQuery = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('currency_code', $currencyCode)
            ->where('direction', 'credit')
            ->whereIn('status', $successfulStatuses);

        $debitedQuery = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('currency_code', $currencyCode)
            ->where('direction', 'debit');

        $maturedCreditAmount = (int) (clone $creditedQuery)
            ->where(function ($query) use ($maturedCutoff, $delayDays): void {
                if ($delayDays > 0) {
                    $query->where('occurred_at', '<=', $maturedCutoff);
                }
            })
            ->sum('net_amount');

        $maturedDebitAmount = (int) (clone $debitedQuery)
            ->whereIn('status', $successfulStatuses)
            ->sum('net_amount');

        $pendingDebitHoldAmount = (int) (clone $debitedQuery)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('net_amount');

        $reserveWindowGross = $reserveRate > 0
            ? (int) (clone $creditedQuery)
                ->when($reserveDays > 0, fn ($query) => $query->where('occurred_at', '>=', $reserveCutoff))
                ->sum('net_amount')
            : 0;

        $reserveHoldAmount = $reserveRate > 0
            ? (int) round($reserveWindowGross * $reserveRate / 100)
            : 0;

        $reservedSettlements = (int) Settlement::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('currency_code', $currencyCode)
            ->whereIn('status', ['pending', 'approved', 'scheduled', 'paid'])
            ->sum('gross_amount');

        $maturedAmount = max(0, $maturedCreditAmount - $maturedDebitAmount);
        $available = max(0, $maturedAmount - $pendingDebitHoldAmount - $reserveHoldAmount - $reservedSettlements);

        return [
            'available_amount' => $available,
            'matured_amount' => $maturedAmount,
            'matured_credit_amount' => $maturedCreditAmount,
            'matured_debit_amount' => $maturedDebitAmount,
            'pending_debit_hold_amount' => $pendingDebitHoldAmount,
            'reserve_hold_amount' => $reserveHoldAmount,
            'reserved_settlement_amount' => $reservedSettlements,
            'currency_code' => $currencyCode,
            'policy' => $policy ? [
                'id' => $policy->getKey(),
                'public_id' => $policy->public_id,
                'name' => $policy->name,
                'minimum_payout_amount' => (int) ($policy->minimum_payout_amount ?? 0),
                'reserve_rate' => (float) ($policy->reserve_rate ?? 0),
                'reserve_days' => (int) ($policy->reserve_days ?? 0),
                'payout_delay_days' => (int) ($policy->payout_delay_days ?? 0),
            ] : null,
        ];
    }

    private function evaluateFee(int $amount, ?string $mode, float $percentageRate, int $fixedAmount, ?int $capAmount): int
    {
        $fee = match ($mode) {
            FeeCalculationMode::Percentage->value => (int) round($amount * $percentageRate / 100),
            FeeCalculationMode::PercentagePlusFixed->value => (int) round($amount * $percentageRate / 100) + $fixedAmount,
            default => $fixedAmount,
        };

        if ($capAmount !== null && $capAmount > 0) {
            $fee = min($fee, $capAmount);
        }

        return max(0, $fee);
    }

    private function scorePolicy(PayoutPolicy $policy, int $tenantId, string $countryCode, string $currencyCode): int
    {
        $score = 0;

        if ((int) ($policy->tenant_id ?? 0) === $tenantId) {
            $score += 10;
        } elseif ($policy->tenant_id === null) {
            $score += 1;
        }

        if (($policy->country_code ?? null) === $countryCode) {
            $score += 10;
        } elseif ($policy->country_code === null) {
            $score += 1;
        }

        if (($policy->currency_code ?? null) === $currencyCode) {
            $score += 10;
        } elseif ($policy->currency_code === null) {
            $score += 1;
        }

        return $score;
    }
}
