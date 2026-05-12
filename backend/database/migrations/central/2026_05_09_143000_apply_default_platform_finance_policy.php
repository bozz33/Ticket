<?php

use App\Enums\CommercialModule;
use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Enums\RefundFeeBehavior;
use App\Models\GatewayFeeRule;
use App\Models\PayoutPolicy;
use App\Models\PlatformFeeRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        GatewayFeeRule::query()
            ->where('is_active', true)
            ->update([
                'charge_bearer' => FeeChargeBearer::Platform->value,
                'is_gross_up_enabled' => false,
                'refund_behavior' => RefundFeeBehavior::NonRefundable->value,
                'updated_at' => now(),
            ]);

        PlatformFeeRule::query()
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        foreach (CommercialModule::cases() as $module) {
            PlatformFeeRule::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'module' => $module->value,
                    'country_code' => null,
                    'currency_code' => null,
                    'name' => sprintf('Commission plateforme 10%% - %s', $module->value),
                ],
                [
                    'public_id' => (string) Str::uuid(),
                    'charge_bearer' => FeeChargeBearer::Organizer->value,
                    'fee_mode' => FeeCalculationMode::Percentage->value,
                    'percentage_rate' => 10,
                    'fixed_amount' => 0,
                    'cap_amount' => null,
                    'vat_rate' => 0,
                    'refund_behavior' => RefundFeeBehavior::Refundable->value,
                    'is_active' => true,
                    'priority' => 10,
                    'effective_from' => null,
                    'effective_to' => null,
                    'meta' => [
                        'system_policy' => 'settlement_only_10_percent',
                        'notes' => 'Commission plateforme unique retenue au reversement organisateur.',
                    ],
                ],
            );
        }

        PayoutPolicy::query()
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        PayoutPolicy::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'country_code' => null,
                'currency_code' => null,
                'name' => 'Reversement standard sans frais',
            ],
            [
                'public_id' => (string) Str::uuid(),
                'minimum_payout_amount' => 0,
                'reserve_rate' => 0,
                'reserve_days' => 0,
                'payout_delay_days' => 0,
                'charge_bearer' => FeeChargeBearer::Organizer->value,
                'payout_fee_mode' => null,
                'payout_fee_percentage' => 0,
                'payout_fee_fixed' => 0,
                'payout_fee_cap' => null,
                'auto_payout_enabled' => false,
                'requires_manual_review' => true,
                'is_active' => true,
                'priority' => 10,
                'effective_from' => null,
                'effective_to' => null,
                'meta' => [
                    'system_policy' => 'settlement_only_10_percent',
                    'notes' => 'Aucun autre frais organisateur n’est appliqué au reversement.',
                ],
            ],
        );
    }

    public function down(): void
    {
        PlatformFeeRule::query()
            ->where('name', 'like', 'Commission plateforme 10% - %')
            ->delete();

        PayoutPolicy::query()
            ->where('name', 'Reversement standard sans frais')
            ->delete();
    }
};
