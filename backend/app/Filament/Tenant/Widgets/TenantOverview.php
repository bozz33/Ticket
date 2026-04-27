<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\PlatformTransaction;
use App\Models\Settlement;
use App\Models\TenantSubscription;
use App\Support\Tenancy\TenantContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return [
                Stat::make('Souscription active', 'Aucune'),
                Stat::make('Transactions', '0'),
                Stat::make('Demandes de reversement', '0'),
                Stat::make('Commission du mois', '0 FCFA'),
                Stat::make('Solde net du mois', '0 FCFA'),
            ];
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $activeSubscription = TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('status', ['active', 'trialing'])
            ->latest('started_at')
            ->first();

        $transactionsQuery = PlatformTransaction::query()->where('tenant_id', $tenant->getKey());
        $monthlyTransactionsQuery = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth]);

        $settlementsQuery = Settlement::query()->where('tenant_id', $tenant->getKey());

        $monthlyCommission = (int) (clone $monthlyTransactionsQuery)->sum('fee_amount');
        $monthlyNet = (int) (clone $monthlyTransactionsQuery)->sum('net_amount');
        $pendingSettlements = (int) (clone $settlementsQuery)->whereIn('status', ['draft', 'scheduled'])->count();
        $transactionCount = (int) (clone $transactionsQuery)->count();

        return [
            Stat::make('Souscription active', $activeSubscription?->plan?->name ?? 'Aucune')
                ->description($activeSubscription?->status?->value ?? 'inactive'),
            Stat::make('Transactions', number_format($transactionCount, 0, ',', ' ')),
            Stat::make('Demandes de reversement', number_format($pendingSettlements, 0, ',', ' ')),
            Stat::make('Commission du mois', number_format($monthlyCommission, 0, ',', ' ') . ' FCFA'),
            Stat::make('Solde net du mois', number_format($monthlyNet, 0, ',', ' ') . ' FCFA'),
        ];
    }
}
