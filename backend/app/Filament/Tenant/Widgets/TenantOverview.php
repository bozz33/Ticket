<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\PlatformTransaction;
use App\Models\Settlement;
use App\Support\Tenancy\TenantContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Ticket\Payments\Domain\PaymentStatuses;

class TenantOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return [
                Stat::make('Transactions', '0'),
                Stat::make('Demandes de reversement', '0'),
                Stat::make('Commission du mois', '0 FCFA'),
                Stat::make('Solde net du mois', '0 FCFA'),
            ];
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $transactionsQuery = PlatformTransaction::query()->where('tenant_id', $tenant->getKey());
        $monthlyTransactionsQuery = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', PaymentStatuses::successful());

        $settlementsQuery = Settlement::query()->where('tenant_id', $tenant->getKey());

        $monthlyTransactions = (clone $monthlyTransactionsQuery)
            ->get(['direction', 'platform_fee_amount', 'net_amount']);
        $monthlyCommission = (int) $monthlyTransactions->sum(function (PlatformTransaction $transaction): int {
            $amount = (int) $transaction->platform_fee_amount;

            return $transaction->direction === 'debit' ? -$amount : $amount;
        });
        $monthlyNet = (int) $monthlyTransactions->sum(function (PlatformTransaction $transaction): int {
            $amount = (int) $transaction->net_amount;

            return $transaction->direction === 'debit' ? -$amount : $amount;
        });
        $pendingSettlements = (int) (clone $settlementsQuery)->whereIn('status', ['pending', 'approved', 'scheduled'])->count();
        $transactionCount = (int) (clone $transactionsQuery)->count();

        return [
            Stat::make('Transactions', number_format($transactionCount, 0, ',', ' ')),
            Stat::make('Demandes de reversement', number_format($pendingSettlements, 0, ',', ' ')),
            Stat::make('Commission du mois', number_format($monthlyCommission, 0, ',', ' ').' FCFA'),
            Stat::make('Solde net du mois', number_format($monthlyNet, 0, ',', ' ').' FCFA'),
        ];
    }
}
