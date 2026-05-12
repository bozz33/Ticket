<?php

namespace App\Filament\Widgets;

use App\Models\PlatformTransaction;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $monthlyTransactions = PlatformTransaction::query()
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['success', 'successful', 'confirmed', 'completed', 'paid'])
            ->get(['direction', 'platform_fee_amount']);

        $monthlyCommission = (int) $monthlyTransactions->sum(function (PlatformTransaction $transaction): int {
            $amount = (int) $transaction->platform_fee_amount;

            return $transaction->direction === 'debit' ? -$amount : $amount;
        });

        return [
            Stat::make('Nombre de société', number_format(Tenant::query()->count(), 0, ',', ' ')),
            Stat::make('Souscriptions actives', number_format(TenantSubscription::query()->where('status', 'active')->count(), 0, ',', ' ')),
            Stat::make('Commission du mois', number_format($monthlyCommission, 0, ',', ' ') . ' FCFA'),
            Stat::make('Nombre de transaction', number_format(PlatformTransaction::query()->count(), 0, ',', ' ')),
            Stat::make('Demande de reversement', number_format(Settlement::query()->whereIn('status', ['pending', 'approved', 'scheduled'])->count(), 0, ',', ' ')),
        ];
    }
}
