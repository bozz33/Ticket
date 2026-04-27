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
        $monthlyCommission = PlatformTransaction::query()
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('fee_amount');

        return [
            Stat::make('Nombre de société', number_format(Tenant::query()->count(), 0, ',', ' ')),
            Stat::make('Souscriptions actives', number_format(TenantSubscription::query()->where('status', 'active')->count(), 0, ',', ' ')),
            Stat::make('Commission du mois', number_format($monthlyCommission, 0, ',', ' ') . ' FCFA'),
            Stat::make('Nombre de transaction', number_format(PlatformTransaction::query()->count(), 0, ',', ' ')),
            Stat::make('Demande de reversement', number_format(Settlement::query()->whereIn('status', ['draft', 'scheduled'])->count(), 0, ',', ' ')),
        ];
    }
}
