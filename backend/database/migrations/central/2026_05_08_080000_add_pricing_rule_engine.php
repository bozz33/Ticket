<?php

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasColumn('payment_gateways', 'supported_countries')) {
            Schema::connection('central')->table('payment_gateways', function (Blueprint $table): void {
                $table->json('supported_countries')->nullable()->after('supported_currencies');
            });
        }

        if (! Schema::connection('central')->hasColumn('payment_gateways', 'supported_channels')) {
            Schema::connection('central')->table('payment_gateways', function (Blueprint $table): void {
                $table->json('supported_channels')->nullable()->after('supported_countries');
            });
        }

        Schema::connection('central')->create('gateway_fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('name');
            $table->string('country_code', 2)->nullable()->index();
            $table->string('currency_code', 3)->nullable()->index();
            $table->string('payment_channel', 50)->nullable()->index();
            $table->string('charge_bearer', 20)->default(FeeChargeBearer::Organizer->value)->index();
            $table->string('fee_mode', 40)->default(FeeCalculationMode::Percentage->value);
            $table->decimal('percentage_rate', 8, 4)->nullable();
            $table->unsignedBigInteger('fixed_amount')->nullable();
            $table->unsignedBigInteger('cap_amount')->nullable();
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_gross_up_enabled')->default(false);
            $table->unsignedInteger('priority')->default(100)->index();
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('module', 80)->nullable()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('currency_code', 3)->nullable()->index();
            $table->string('charge_bearer', 20)->default(FeeChargeBearer::Buyer->value)->index();
            $table->string('fee_mode', 40)->default(FeeCalculationMode::Percentage->value);
            $table->decimal('percentage_rate', 8, 4)->nullable();
            $table->unsignedBigInteger('fixed_amount')->nullable();
            $table->unsignedBigInteger('cap_amount')->nullable();
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('payout_policies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('country_code', 2)->nullable()->index();
            $table->string('currency_code', 3)->nullable()->index();
            $table->unsignedBigInteger('minimum_payout_amount')->default(1000);
            $table->decimal('reserve_rate', 8, 4)->default(0);
            $table->unsignedInteger('reserve_days')->default(0);
            $table->unsignedInteger('payout_delay_days')->default(0);
            $table->string('charge_bearer', 20)->default(FeeChargeBearer::Organizer->value)->index();
            $table->string('payout_fee_mode', 40)->nullable();
            $table->decimal('payout_fee_percentage', 8, 4)->nullable();
            $table->unsignedBigInteger('payout_fee_fixed')->nullable();
            $table->unsignedBigInteger('payout_fee_cap')->nullable();
            $table->boolean('auto_payout_enabled')->default(false);
            $table->boolean('requires_manual_review')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->table('platform_transactions', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('platform_transactions', 'gateway_fee_amount')) {
                $table->bigInteger('gateway_fee_amount')->default(0)->after('net_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'platform_fee_amount')) {
                $table->bigInteger('platform_fee_amount')->default(0)->after('gateway_fee_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'tax_amount')) {
                $table->bigInteger('tax_amount')->default(0)->after('platform_fee_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'payout_fee_amount')) {
                $table->bigInteger('payout_fee_amount')->default(0)->after('tax_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'customer_fee_amount')) {
                $table->bigInteger('customer_fee_amount')->default(0)->after('payout_fee_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'absorbed_fee_amount')) {
                $table->bigInteger('absorbed_fee_amount')->default(0)->after('customer_fee_amount');
            }

            if (! Schema::connection('central')->hasColumn('platform_transactions', 'pricing_snapshot')) {
                $table->json('pricing_snapshot')->nullable()->after('meta');
            }
        });

        Schema::connection('central')->table('settlements', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('settlements', 'payout_policy_id')) {
                $table->foreignId('payout_policy_id')->nullable()->after('payout_batch_id')->constrained('payout_policies')->nullOnDelete();
            }

            if (! Schema::connection('central')->hasColumn('settlements', 'reserve_amount')) {
                $table->bigInteger('reserve_amount')->default(0)->after('fee_amount');
            }

            if (! Schema::connection('central')->hasColumn('settlements', 'payout_fee_amount')) {
                $table->bigInteger('payout_fee_amount')->default(0)->after('reserve_amount');
            }

            if (! Schema::connection('central')->hasColumn('settlements', 'pricing_snapshot')) {
                $table->json('pricing_snapshot')->nullable()->after('meta');
            }
        });

        $connection = DB::connection('central');
        $now = now();
        $paystack = $connection->table('payment_gateways')->where('code', 'paystack')->first();

        if ($paystack) {
            $connection->table('payment_gateways')
                ->where('id', $paystack->id)
                ->update([
                    'supported_countries' => json_encode(['CI', 'GH', 'KE', 'NG', 'ZA']),
                    'supported_channels' => json_encode(['card', 'card_local', 'card_international', 'mobile_money', 'bank_transfer']),
                    'updated_at' => $now,
                ]);

            $defaultGatewayRules = [
                ['country_code' => 'CI', 'currency_code' => 'XOF', 'payment_channel' => 'card', 'percentage_rate' => 3.2, 'fixed_amount' => 0, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack CI Carte'],
                ['country_code' => 'CI', 'currency_code' => 'XOF', 'payment_channel' => 'mobile_money', 'percentage_rate' => 1.95, 'fixed_amount' => 0, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack CI Mobile Money'],
                ['country_code' => 'GH', 'currency_code' => 'GHS', 'payment_channel' => 'card', 'percentage_rate' => 1.95, 'fixed_amount' => 0, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack GH Carte'],
                ['country_code' => 'KE', 'currency_code' => 'KES', 'payment_channel' => 'card', 'percentage_rate' => 2.9, 'fixed_amount' => 0, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack KE Carte'],
                ['country_code' => 'KE', 'currency_code' => 'KES', 'payment_channel' => 'mobile_money', 'percentage_rate' => 1.5, 'fixed_amount' => 0, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack KE M-PESA'],
                ['country_code' => 'NG', 'currency_code' => 'NGN', 'payment_channel' => 'card', 'percentage_rate' => 1.5, 'fixed_amount' => 100, 'cap_amount' => 2000, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack NG Carte'],
                ['country_code' => 'ZA', 'currency_code' => 'ZAR', 'payment_channel' => 'card', 'percentage_rate' => 2.9, 'fixed_amount' => 100, 'charge_bearer' => FeeChargeBearer::Organizer->value, 'name' => 'Paystack ZA Carte'],
            ];

            foreach ($defaultGatewayRules as $rule) {
                $connection->table('gateway_fee_rules')->updateOrInsert(
                    [
                        'payment_gateway_id' => $paystack->id,
                        'country_code' => $rule['country_code'],
                        'currency_code' => $rule['currency_code'],
                        'payment_channel' => $rule['payment_channel'],
                    ],
                    [
                        'public_id' => (string) Str::uuid(),
                        'name' => $rule['name'],
                        'charge_bearer' => $rule['charge_bearer'],
                        'fee_mode' => $rule['fixed_amount'] > 0 ? FeeCalculationMode::PercentagePlusFixed->value : FeeCalculationMode::Percentage->value,
                        'percentage_rate' => $rule['percentage_rate'],
                        'fixed_amount' => $rule['fixed_amount'],
                        'cap_amount' => $rule['cap_amount'] ?? null,
                        'vat_rate' => 0,
                        'is_active' => true,
                        'is_gross_up_enabled' => false,
                        'priority' => 100,
                        'meta' => json_encode(['seeded_from' => 'paystack_official_pricing']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }

        $legacyPolicies = $connection->table('commercial_policies')->where('is_active', true)->get();

        foreach ($legacyPolicies as $legacyPolicy) {
            $percentageRate = $legacyPolicy->commission_rate !== null ? (float) $legacyPolicy->commission_rate : 0;
            $fixedAmount = $legacyPolicy->flat_fee_amount !== null ? (int) $legacyPolicy->flat_fee_amount : 0;

            if ($percentageRate <= 0 && $fixedAmount <= 0) {
                continue;
            }

            $feeMode = $percentageRate > 0 && $fixedAmount > 0
                ? FeeCalculationMode::PercentagePlusFixed->value
                : ($percentageRate > 0 ? FeeCalculationMode::Percentage->value : FeeCalculationMode::Fixed->value);

            $connection->table('platform_fee_rules')->updateOrInsert(
                [
                    'tenant_id' => null,
                    'module' => $legacyPolicy->module,
                    'currency_code' => strtoupper((string) ($legacyPolicy->currency_code ?: 'XOF')),
                    'name' => 'Legacy '.$legacyPolicy->module,
                ],
                [
                    'public_id' => (string) Str::uuid(),
                    'country_code' => null,
                    'charge_bearer' => FeeChargeBearer::Buyer->value,
                    'fee_mode' => $feeMode,
                    'percentage_rate' => $percentageRate,
                    'fixed_amount' => $fixedAmount,
                    'cap_amount' => null,
                    'vat_rate' => 0,
                    'is_active' => true,
                    'priority' => 100,
                    'meta' => json_encode([
                        'seeded_from' => 'commercial_policies',
                        'legacy_policy_id' => $legacyPolicy->id,
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $connection->table('payout_policies')->updateOrInsert(
            [
                'tenant_id' => null,
                'name' => 'Politique de reversement par défaut',
                'currency_code' => 'XOF',
            ],
            [
                'public_id' => (string) Str::uuid(),
                'country_code' => 'CI',
                'minimum_payout_amount' => 1000,
                'reserve_rate' => 5,
                'reserve_days' => 7,
                'payout_delay_days' => 2,
                'charge_bearer' => FeeChargeBearer::Organizer->value,
                'payout_fee_mode' => FeeCalculationMode::PercentagePlusFixed->value,
                'payout_fee_percentage' => 1,
                'payout_fee_fixed' => 100,
                'payout_fee_cap' => null,
                'auto_payout_enabled' => false,
                'requires_manual_review' => true,
                'is_active' => true,
                'priority' => 100,
                'meta' => json_encode(['seeded' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        Schema::connection('central')->table('settlements', function (Blueprint $table): void {
            foreach (['payout_policy_id', 'reserve_amount', 'payout_fee_amount', 'pricing_snapshot'] as $column) {
                if (Schema::connection('central')->hasColumn('settlements', $column)) {
                    if ($column === 'payout_policy_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::connection('central')->table('platform_transactions', function (Blueprint $table): void {
            foreach (['gateway_fee_amount', 'platform_fee_amount', 'tax_amount', 'payout_fee_amount', 'customer_fee_amount', 'absorbed_fee_amount', 'pricing_snapshot'] as $column) {
                if (Schema::connection('central')->hasColumn('platform_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::connection('central')->dropIfExists('payout_policies');
        Schema::connection('central')->dropIfExists('platform_fee_rules');
        Schema::connection('central')->dropIfExists('gateway_fee_rules');

        Schema::connection('central')->table('payment_gateways', function (Blueprint $table): void {
            foreach (['supported_countries', 'supported_channels'] as $column) {
                if (Schema::connection('central')->hasColumn('payment_gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
