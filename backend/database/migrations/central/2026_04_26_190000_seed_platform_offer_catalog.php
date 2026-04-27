<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $featureFlags = [
            [
                'code' => 'tenant.access',
                'name' => 'Accès backoffice tenant',
                'description' => 'Autorise l’accès général au panneau tenant.',
                'module' => 'core',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'core'],
            ],
            [
                'code' => 'tenant.users',
                'name' => 'Gestion des utilisateurs tenant',
                'description' => 'Autorise la gestion des utilisateurs et des rôles.',
                'module' => 'core',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'core'],
            ],
            [
                'code' => 'tenant.finance',
                'name' => 'Finance tenant',
                'description' => 'Expose les données financières tenant.',
                'module' => 'finance',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'core'],
            ],
            [
                'code' => 'tenant.support',
                'name' => 'Support tenant',
                'description' => 'Expose les outils de support côté tenant.',
                'module' => 'support',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'core'],
            ],
            [
                'code' => 'tenant.ticketing',
                'name' => 'Billetterie',
                'description' => 'Active la vente de tickets et la gestion des accès.',
                'module' => 'ticketing',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'module'],
            ],
            [
                'code' => 'tenant.stands',
                'name' => 'Stands',
                'description' => 'Active la réservation et la gestion des stands B2B.',
                'module' => 'stands',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'module'],
            ],
            [
                'code' => 'tenant.calls_for_projects',
                'name' => 'Appels à projets',
                'description' => 'Active les dépôts de candidatures et dossiers.',
                'module' => 'calls_for_projects',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'module'],
            ],
            [
                'code' => 'tenant.training',
                'name' => 'Formations',
                'description' => 'Active le catalogue de formations et les inscriptions.',
                'module' => 'training',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'module'],
            ],
            [
                'code' => 'tenant.crowdfunding',
                'name' => 'Crowdfunding',
                'description' => 'Active les campagnes de dons et la progression publique.',
                'module' => 'crowdfunding',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'module'],
            ],
            [
                'code' => 'tenant.custom_domain',
                'name' => 'Sous-domaine personnalisé',
                'description' => 'Autorise la personnalisation avancée du domaine public.',
                'module' => 'branding',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'premium'],
            ],
            [
                'code' => 'tenant.priority_support',
                'name' => 'Support 24/7 prioritaire',
                'description' => 'Expose un niveau de support premium.',
                'module' => 'support',
                'default_enabled' => false,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => ['group' => 'premium'],
            ],
        ];

        $connection->table('feature_flags')->upsert(
            collect($featureFlags)->map(fn (array $flag): array => [
                'code' => $flag['code'],
                'name' => $flag['name'],
                'description' => $flag['description'],
                'module' => $flag['module'],
                'default_enabled' => $flag['default_enabled'],
                'requires_subscription' => $flag['requires_subscription'],
                'is_public' => $flag['is_public'],
                'is_active' => $flag['is_active'],
                'meta' => json_encode($flag['meta'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['code'],
            ['name', 'description', 'module', 'default_enabled', 'requires_subscription', 'is_public', 'is_active', 'meta', 'updated_at'],
        );

        $plans = [
            'starter' => [
                'name' => 'Starter',
                'description' => 'Billetterie de base uniquement, avec commissions plus élevées.',
                'price_amount' => 0,
                'currency_code' => 'XOF',
                'billing_interval' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'meta' => [
                    'features' => [
                        'tenant.access',
                        'tenant.users',
                        'tenant.finance',
                        'tenant.support',
                        'tenant.ticketing',
                    ],
                    'support_level' => 'standard',
                    'commercial_profile' => 'starter',
                ],
            ],
            'business' => [
                'name' => 'Business',
                'description' => 'Billetterie, stands, appels à projets et formations avec commissions standards.',
                'price_amount' => 25000,
                'currency_code' => 'XOF',
                'billing_interval' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'meta' => [
                    'features' => [
                        'tenant.access',
                        'tenant.users',
                        'tenant.finance',
                        'tenant.support',
                        'tenant.ticketing',
                        'tenant.stands',
                        'tenant.calls_for_projects',
                        'tenant.training',
                    ],
                    'support_level' => 'standard',
                    'commercial_profile' => 'business',
                ],
            ],
            'premium' => [
                'name' => 'Premium',
                'description' => 'Tous les modules, crowdfunding, sous-domaine personnalisé et support 24/7.',
                'price_amount' => 60000,
                'currency_code' => 'XOF',
                'billing_interval' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'meta' => [
                    'features' => [
                        'tenant.access',
                        'tenant.users',
                        'tenant.finance',
                        'tenant.support',
                        'tenant.ticketing',
                        'tenant.stands',
                        'tenant.calls_for_projects',
                        'tenant.training',
                        'tenant.crowdfunding',
                        'tenant.custom_domain',
                        'tenant.priority_support',
                    ],
                    'support_level' => '24/7',
                    'commercial_profile' => 'premium',
                ],
            ],
        ];

        foreach ($plans as $code => $plan) {
            $existing = $connection->table('plans')->where('code', $code)->first();

            $connection->table('plans')->updateOrInsert(
                ['code' => $code],
                [
                    'public_id' => $existing?->public_id ?? (string) Str::uuid(),
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'price_amount' => $plan['price_amount'],
                    'currency_code' => $plan['currency_code'],
                    'billing_interval' => $plan['billing_interval'],
                    'trial_days' => $plan['trial_days'],
                    'is_active' => $plan['is_active'],
                    'meta' => json_encode($plan['meta'], JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }

        $planIds = $connection->table('plans')->whereIn('code', array_keys($plans))->pluck('id', 'code');

        $policies = [
            [
                'module' => 'ticketing',
                'monetization_mode' => 'hybrid',
                'plan_id' => null,
                'commission_rate' => 5.00,
                'flat_fee_amount' => 100,
                'currency_code' => 'XOF',
                'is_active' => true,
                'meta' => [
                    'available_plans' => ['starter', 'business', 'premium'],
                    'plan_rules' => [
                        'starter' => ['commission_rate' => 10.00, 'flat_fee_amount' => 100],
                        'business' => ['commission_rate' => 5.00, 'flat_fee_amount' => 100],
                        'premium' => ['commission_rate' => 5.00, 'flat_fee_amount' => 100],
                    ],
                ],
            ],
            [
                'module' => 'stands',
                'monetization_mode' => 'commission',
                'plan_id' => $planIds['business'] ?? null,
                'commission_rate' => 7.00,
                'flat_fee_amount' => null,
                'currency_code' => 'XOF',
                'is_active' => true,
                'meta' => [
                    'available_plans' => ['business', 'premium'],
                ],
            ],
            [
                'module' => 'training',
                'monetization_mode' => 'subscription',
                'plan_id' => $planIds['business'] ?? null,
                'commission_rate' => null,
                'flat_fee_amount' => null,
                'currency_code' => 'XOF',
                'is_active' => true,
                'meta' => [
                    'available_plans' => ['business', 'premium'],
                ],
            ],
            [
                'module' => 'calls_for_projects',
                'monetization_mode' => 'subscription',
                'plan_id' => $planIds['business'] ?? null,
                'commission_rate' => null,
                'flat_fee_amount' => null,
                'currency_code' => 'XOF',
                'is_active' => true,
                'meta' => [
                    'available_plans' => ['business', 'premium'],
                ],
            ],
            [
                'module' => 'crowdfunding',
                'monetization_mode' => 'commission',
                'plan_id' => $planIds['premium'] ?? null,
                'commission_rate' => 5.00,
                'flat_fee_amount' => null,
                'currency_code' => 'XOF',
                'is_active' => true,
                'meta' => [
                    'available_plans' => ['premium'],
                ],
            ],
        ];

        foreach ($policies as $policy) {
            $existing = $connection->table('commercial_policies')->where('module', $policy['module'])->first();

            $connection->table('commercial_policies')->updateOrInsert(
                ['module' => $policy['module']],
                [
                    'monetization_mode' => $policy['monetization_mode'],
                    'plan_id' => $policy['plan_id'],
                    'commission_rate' => $policy['commission_rate'],
                    'flat_fee_amount' => $policy['flat_fee_amount'],
                    'currency_code' => $policy['currency_code'],
                    'is_active' => $policy['is_active'],
                    'meta' => json_encode($policy['meta'], JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }

        $settings = [
            [
                'group' => 'branding',
                'key' => 'experience_theme',
                'type' => 'json',
                'is_public' => true,
                'value' => [
                    'mode' => 'dark',
                    'style' => 'cinema',
                    'ui' => 'minimal',
                ],
            ],
            [
                'group' => 'localization',
                'key' => 'default_currency',
                'type' => 'json',
                'is_public' => true,
                'value' => [
                    'code' => 'XOF',
                    'locale' => 'fr',
                ],
            ],
            [
                'group' => 'payments',
                'key' => 'mobile_money_providers',
                'type' => 'json',
                'is_public' => true,
                'value' => [
                    'orange_money' => true,
                    'mtn_money' => true,
                    'wave' => true,
                    'moov_money' => true,
                ],
            ],
        ];

        foreach ($settings as $setting) {
            $existing = $connection->table('platform_settings')->where('key', $setting['key'])->first();

            $connection->table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                    'value' => json_encode($setting['value'], JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        $connection = DB::connection('central');

        $connection->table('commercial_policies')->whereIn('module', [
            'ticketing',
            'stands',
            'training',
            'calls_for_projects',
            'crowdfunding',
        ])->delete();

        $connection->table('plans')->whereIn('code', [
            'starter',
            'business',
            'premium',
        ])->delete();

        $connection->table('feature_flags')->whereIn('code', [
            'tenant.ticketing',
            'tenant.stands',
            'tenant.calls_for_projects',
            'tenant.training',
            'tenant.crowdfunding',
            'tenant.custom_domain',
            'tenant.priority_support',
        ])->delete();

        $connection->table('platform_settings')->whereIn('key', [
            'experience_theme',
            'default_currency',
            'mobile_money_providers',
        ])->delete();
    }
};
