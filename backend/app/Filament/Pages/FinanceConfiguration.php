<?php

namespace App\Filament\Pages;

use App\Filament\Platform\Resources\PayoutPolicies\PayoutPolicyResource;
use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use App\Filament\Platform\Resources\Refunds\RefundResource;
use App\Filament\Platform\Resources\Settlements\SettlementResource;
use App\Models\PayoutPolicy;
use App\Services\FinancePolicyService;
use Filament\Pages\Page;

class FinanceConfiguration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Politique financière';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.finance-configuration';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return false;
    }

    public function getHeading(): string
    {
        return 'Politique financière plateforme';
    }

    public function getSubheading(): ?string
    {
        return 'Pilotez un modèle simple: commission organisateur configurable, frais carte par ticket configurables, et frais gateway absorbés par la plateforme.';
    }

    protected function getViewData(): array
    {
        $financeSetting = app(FinancePolicyService::class)->ensureSetting();
        $financePolicy = app(FinancePolicyService::class)->current();
        $activePayoutPolicy = PayoutPolicy::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        return [
            'summary' => [
                'commission_rate' => $financePolicy['commission_rate'],
                'card_fee_per_ticket' => $financePolicy['card_fee_per_ticket'],
                'payout_fee_mode' => $activePayoutPolicy?->payout_fee_mode?->value ?? $activePayoutPolicy?->payout_fee_mode,
                'payout_fee_percentage' => $activePayoutPolicy?->payout_fee_percentage,
                'payout_fee_fixed' => $activePayoutPolicy?->payout_fee_fixed,
            ],
            'cards' => [
                [
                    'title' => 'Politique financière',
                    'description' => 'Définissez le taux de commission organisateur et les frais carte par ticket. Laisser vide revient à 0 frais et 0 commission.',
                    'url' => PlatformSettingResource::getUrl('edit', ['record' => $financeSetting]),
                    'stats' => [
                        'Commission' => $financePolicy['commission_rate'] > 0 ? number_format((float) $financePolicy['commission_rate'], 2, ',', ' ').' %' : '0 %',
                        'Carte / ticket' => $financePolicy['card_fee_per_ticket'] > 0 ? number_format((int) $financePolicy['card_fee_per_ticket'], 0, ',', ' ').' FCFA' : '0 FCFA',
                    ],
                ],
                [
                    'title' => 'Politiques de reversement',
                    'description' => 'Configurez minimum de reversement, réserve, délai et validation. Aucun frais gateway ou commission additionnelle ne doit être reparamétré ici.',
                    'url' => PayoutPolicyResource::getUrl('index'),
                    'stats' => [
                        'Actives' => PayoutPolicy::query()->where('is_active', true)->count(),
                        'Total' => PayoutPolicy::query()->count(),
                    ],
                ],
                [
                    'title' => 'Reversements',
                    'description' => 'Consultez les demandes de reversement et le net organisateur après déduction des frais configurés.',
                    'url' => SettlementResource::getUrl('index'),
                ],
                [
                    'title' => 'Remboursements',
                    'description' => 'Déclenchez et suivez les remboursements en tenant compte du supplément carte non remboursable sauf erreur technique ou doublon.',
                    'url' => RefundResource::getUrl('index'),
                ],
                [
                    'title' => 'Transactions',
                    'description' => 'Registre financier en lecture seule pour audit, contrôle et rapprochement des paiements.',
                    'url' => PlatformTransactionResource::getUrl('index'),
                ],
            ],
            'finance_setting_updated_at' => $financeSetting->updated_at,
        ];
    }
}
