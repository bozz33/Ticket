<?php

namespace App\Filament\Tenant\Resources\Settlements\Pages;

use App\Filament\Tenant\Resources\Settlements\SettlementResource;
use App\Services\Payments\PayoutPolicyService;
use App\Support\Tenancy\TenantContext;
use App\Filament\Support\Pages\CreateRecordPage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CreateSettlement extends CreateRecordPage
{
    protected static string $resource = SettlementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = app(TenantContext::class)->get();
        $amount = (int) ($data['gross_amount'] ?? 0);
        $availableBalanceSummary = SettlementResource::availableBalanceSummary();
        $availableBalance = (int) ($availableBalanceSummary['available_amount'] ?? 0);
        $minimumPayoutAmount = SettlementResource::minimumPayoutAmount();

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant introuvable.',
            ]);
        }

        if ($amount < $minimumPayoutAmount) {
            throw ValidationException::withMessages([
                'gross_amount' => 'Le montant demandé est inférieur au minimum autorisé.',
            ]);
        }

        if ($amount > $availableBalance) {
            throw ValidationException::withMessages([
                'gross_amount' => 'Le montant demandé dépasse le solde disponible estimé.',
            ]);
        }

        $payoutPreview = app(PayoutPolicyService::class)->computePayout(
            $tenant,
            $amount,
            strtoupper((string) ($data['currency_code'] ?? $tenant->currency_code ?? 'XOF')),
        );

        return [
            ...$data,
            'tenant_id' => $tenant->getKey(),
            'reference' => 'SET-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
            'status' => 'pending',
            'period_end' => now()->toDateString(),
            'gross_amount' => $amount,
            'fee_amount' => (int) ($payoutPreview['fee_amount'] ?? 0),
            'reserve_amount' => (int) data_get($availableBalanceSummary, 'reserve_hold_amount', 0),
            'payout_fee_amount' => (int) ($payoutPreview['fee_amount'] ?? 0),
            'net_amount' => (int) ($payoutPreview['net_amount'] ?? $amount),
            'currency_code' => strtoupper((string) ($data['currency_code'] ?? 'XOF')),
            'scheduled_at' => null,
            'paid_at' => null,
            'payout_policy_id' => data_get($payoutPreview, 'policy.id'),
            'pricing_snapshot' => $payoutPreview,
            'meta' => array_merge((array) ($data['meta'] ?? []), [
                'requested_from' => 'tenant_panel',
                'available_balance_at_request' => $availableBalance,
                'available_balance_breakdown' => $availableBalanceSummary,
                'payout_preview' => $payoutPreview,
            ]),
        ];
    }
}
