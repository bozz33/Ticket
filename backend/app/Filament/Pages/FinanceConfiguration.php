<?php

namespace App\Filament\Pages;

use App\Filament\Platform\Resources\GatewayFeeRules\GatewayFeeRuleResource;
use App\Filament\Platform\Resources\PayoutPolicies\PayoutPolicyResource;
use App\Filament\Platform\Resources\PlatformFeeRules\PlatformFeeRuleResource;
use App\Filament\Platform\Resources\PlatformTransactions\PlatformTransactionResource;
use App\Filament\Platform\Resources\Refunds\RefundResource;
use App\Filament\Platform\Resources\Settlements\SettlementResource;
use App\Models\GatewayFeeRule;
use App\Models\PayoutPolicy;
use App\Models\PlatformFeeRule;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class FinanceConfiguration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Configuration finance';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.finance-configuration';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        foreach ([
            'platform.gateway_fee_rules.view',
            'platform.platform_fee_rules.view',
            'platform.payout_policies.view',
            'platform.refunds.view',
            'platform.settlements.view',
            'platform.platform_transactions.view',
        ] as $permission) {
            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function getHeading(): string
    {
        return 'Configuration finance plateforme';
    }

    public function getSubheading(): ?string
    {
        return 'Votre configuration active peut retenir une commission organisateur, absorber les frais gateway et piloter le net reversé sans bricolage manuel.';
    }

    protected function getViewData(): array
    {
        $activeCommissionRule = PlatformFeeRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        $activePayoutPolicy = PayoutPolicy::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        return [
            'summary' => [
                'commission_rate' => $activeCommissionRule?->percentage_rate,
                'commission_bearer' => $activeCommissionRule?->charge_bearer?->value ?? $activeCommissionRule?->charge_bearer,
                'commission_mode' => $activeCommissionRule?->fee_mode?->value ?? $activeCommissionRule?->fee_mode,
                'payout_fee_mode' => $activePayoutPolicy?->payout_fee_mode?->value ?? $activePayoutPolicy?->payout_fee_mode,
                'payout_fee_percentage' => $activePayoutPolicy?->payout_fee_percentage,
                'payout_fee_fixed' => $activePayoutPolicy?->payout_fee_fixed,
            ],
            'cards' => [
                [
                    'title' => 'Frais gateway',
                    'description' => 'Définissez les frais Paystack et autres providers par pays, devise, canal, taxation et porteur des frais. Pour votre modèle actuel, ils peuvent être absorbés par la plateforme.',
                    'url' => GatewayFeeRuleResource::getUrl('index'),
                    'stats' => [
                        'Actives' => GatewayFeeRule::query()->where('is_active', true)->count(),
                        'Total' => GatewayFeeRule::query()->count(),
                    ],
                ],
                [
                    'title' => 'Commissions plateforme',
                    'description' => 'Pilotez la commission appliquée aux organisateurs selon le module, le tenant, le pays et la devise. Votre règle cible actuelle: 10% sur les ventes.',
                    'url' => PlatformFeeRuleResource::getUrl('index'),
                    'stats' => [
                        'Actives' => PlatformFeeRule::query()->where('is_active', true)->count(),
                        'Total' => PlatformFeeRule::query()->count(),
                    ],
                ],
                [
                    'title' => 'Politiques de reversement',
                    'description' => 'Configurez minimum de reversement, réserve, délai, frais de payout, automatisation et validation manuelle. Dans votre modèle, aucun frais supplémentaire ne doit être ajouté ici.',
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
                    'description' => 'Déclenchez et suivez les remboursements en gardant l’historique financier et le calcul des frais.',
                    'url' => RefundResource::getUrl('index'),
                ],
                [
                    'title' => 'Transactions',
                    'description' => 'Registre financier en lecture seule pour audit, contrôle et rapprochement des paiements.',
                    'url' => PlatformTransactionResource::getUrl('index'),
                ],
            ],
        ];
    }
}
