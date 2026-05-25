<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $featureFlags = [
            ['code' => 'tenant.access', 'name' => 'Acces backoffice tenant', 'description' => 'Autorise l acces general au panneau tenant.', 'module' => 'core', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'core']],
            ['code' => 'tenant.users', 'name' => 'Gestion des utilisateurs tenant', 'description' => 'Autorise la gestion des utilisateurs et des roles.', 'module' => 'core', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'core']],
            ['code' => 'tenant.finance', 'name' => 'Finance tenant', 'description' => 'Expose les donnees financieres tenant.', 'module' => 'finance', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'core']],
            ['code' => 'tenant.support', 'name' => 'Support tenant', 'description' => 'Expose les outils de support cote tenant.', 'module' => 'support', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'core']],
            ['code' => 'tenant.ticketing', 'name' => 'Billetterie', 'description' => 'Active la vente de tickets et la gestion des acces.', 'module' => 'ticketing', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'module']],
            ['code' => 'tenant.stands', 'name' => 'Stands', 'description' => 'Active la reservation et la gestion des stands B2B.', 'module' => 'stands', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'module']],
            ['code' => 'tenant.calls_for_projects', 'name' => 'Appels a projets', 'description' => 'Active les depots de candidatures et dossiers.', 'module' => 'calls_for_projects', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'module']],
            ['code' => 'tenant.training', 'name' => 'Formations', 'description' => 'Active le catalogue de formations et les inscriptions.', 'module' => 'training', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'module']],
            ['code' => 'tenant.crowdfunding', 'name' => 'Crowdfunding', 'description' => 'Active les campagnes de contribution et la progression publique.', 'module' => 'crowdfunding', 'default_enabled' => true, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'module']],
            ['code' => 'tenant.custom_domain', 'name' => 'Domaine personnalise', 'description' => 'Autorise la personnalisation avancee du domaine public.', 'module' => 'branding', 'default_enabled' => false, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'advanced']],
            ['code' => 'tenant.priority_support', 'name' => 'Support prioritaire', 'description' => 'Expose un niveau de support prioritaire.', 'module' => 'support', 'default_enabled' => false, 'is_public' => false, 'is_active' => true, 'meta' => ['group' => 'advanced']],
        ];

        $connection->table('feature_flags')->upsert(
            collect($featureFlags)->map(fn (array $flag): array => [
                'code' => $flag['code'],
                'name' => $flag['name'],
                'description' => $flag['description'],
                'module' => $flag['module'],
                'default_enabled' => $flag['default_enabled'],
                'is_public' => $flag['is_public'],
                'is_active' => $flag['is_active'],
                'meta' => json_encode($flag['meta'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['code'],
            ['name', 'description', 'module', 'default_enabled', 'is_public', 'is_active', 'meta', 'updated_at'],
        );

        $policies = [
            ['module' => 'ticketing', 'monetization_mode' => 'hybrid', 'commission_rate' => 5.00, 'flat_fee_amount' => 100, 'currency_code' => 'XOF', 'is_active' => true, 'meta' => ['profile' => 'ticketing']],
            ['module' => 'stands', 'monetization_mode' => 'commission', 'commission_rate' => 7.00, 'flat_fee_amount' => null, 'currency_code' => 'XOF', 'is_active' => true, 'meta' => ['profile' => 'stands']],
            ['module' => 'training', 'monetization_mode' => 'commission', 'commission_rate' => 5.00, 'flat_fee_amount' => null, 'currency_code' => 'XOF', 'is_active' => true, 'meta' => ['profile' => 'training']],
            ['module' => 'calls_for_projects', 'monetization_mode' => 'commission', 'commission_rate' => 5.00, 'flat_fee_amount' => null, 'currency_code' => 'XOF', 'is_active' => true, 'meta' => ['profile' => 'calls_for_projects']],
            ['module' => 'crowdfunding', 'monetization_mode' => 'commission', 'commission_rate' => 5.00, 'flat_fee_amount' => null, 'currency_code' => 'XOF', 'is_active' => true, 'meta' => ['profile' => 'crowdfunding']],
        ];

        foreach ($policies as $policy) {
            $existing = $connection->table('commercial_policies')->where('module', $policy['module'])->first();

            $connection->table('commercial_policies')->updateOrInsert(
                ['module' => $policy['module']],
                [
                    'monetization_mode' => $policy['monetization_mode'],
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
            ['group' => 'branding', 'key' => 'experience_theme', 'type' => 'json', 'is_public' => true, 'value' => ['mode' => 'dark', 'style' => 'cinema', 'ui' => 'minimal']],
            ['group' => 'localization', 'key' => 'default_currency', 'type' => 'json', 'is_public' => true, 'value' => ['code' => 'XOF', 'locale' => 'fr']],
            ['group' => 'payments', 'key' => 'mobile_money_providers', 'type' => 'json', 'is_public' => true, 'value' => ['orange_money' => true, 'mtn_money' => true, 'wave' => true, 'moov_money' => true]],
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
