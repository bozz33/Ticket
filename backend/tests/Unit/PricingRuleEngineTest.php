<?php

namespace Tests\Unit;

use App\Enums\CommercialModule;
use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Models\GatewayFeeRule;
use App\Models\Offer;
use App\Models\PaymentGateway;
use App\Models\PayoutPolicy;
use App\Models\PlatformFeeRule;
use App\Models\PlatformTransaction;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Services\Payments\PayoutPolicyService;
use App\Services\Payments\PricingRuleEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PricingRuleEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.central.driver', 'sqlite');
        config()->set('database.connections.central.database', ':memory:');
        config()->set('database.connections.central.foreign_key_constraints', true);

        DB::purge('central');

        $this->prepareCentralSchema();
    }

    public function test_pricing_rule_engine_applies_gateway_and_platform_rules_with_fee_bearers(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) fake()->uuid(),
            'name' => 'Tenant Pricing',
            'slug' => 'tenant-pricing',
            'status' => 'active',
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'locale' => 'fr',
            'timezone' => 'UTC',
            'database_name' => 'tenant_pricing',
        ]));

        $gateway = PaymentGateway::query()->create([
            'code' => 'paystack',
            'name' => 'Paystack',
            'provider' => 'paystack',
            'mode' => 'test',
            'is_active' => true,
            'supported_currencies' => ['XOF'],
            'supported_countries' => ['CI'],
            'supported_channels' => ['card'],
        ]);

        GatewayFeeRule::query()->create([
            'payment_gateway_id' => $gateway->getKey(),
            'name' => 'Paystack CI Card',
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'payment_channel' => 'card',
            'charge_bearer' => FeeChargeBearer::Organizer,
            'fee_mode' => FeeCalculationMode::Percentage,
            'percentage_rate' => 2.0,
            'is_active' => true,
            'priority' => 10,
        ]);

        PlatformFeeRule::query()->create([
            'tenant_id' => null,
            'name' => 'Ticketing Buyer Fee',
            'module' => CommercialModule::Ticketing,
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'charge_bearer' => FeeChargeBearer::Buyer,
            'fee_mode' => FeeCalculationMode::PercentagePlusFixed,
            'percentage_rate' => 10,
            'fixed_amount' => 200,
            'is_active' => true,
            'priority' => 10,
        ]);

        $offer = new Offer([
            'offerable_type' => \App\Models\Event::class,
            'price_amount' => 10000,
            'currency_code' => 'XOF',
        ]);

        $quote = app(PricingRuleEngine::class)->quote($tenant, $offer, 1, $gateway, 'card');

        $this->assertSame(10000, $quote['subtotal']);
        $this->assertSame(11200, $quote['customer_total']);
        $this->assertSame(1200, $quote['customer_fee_total']);
        $this->assertSame(200, $quote['organizer_fee_total']);
        $this->assertSame(9800, $quote['organizer_net']);
        $this->assertSame(200, $quote['gateway_fee_amount']);
        $this->assertSame(1200, $quote['platform_fee_amount']);
        $this->assertSame('card', $quote['payment_channel']);
        $this->assertSame('buyer', data_get($quote, 'breakdown.platform_fee.charge_bearer'));
        $this->assertSame('organizer', data_get($quote, 'breakdown.gateway_fee.charge_bearer'));
    }

    public function test_payout_policy_service_computes_fee_and_available_balance(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) fake()->uuid(),
            'name' => 'Tenant Payout',
            'slug' => 'tenant-payout',
            'status' => 'active',
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'locale' => 'fr',
            'timezone' => 'UTC',
            'database_name' => 'tenant_payout',
        ]));

        PayoutPolicy::query()->create([
            'tenant_id' => null,
            'name' => 'Default XOF Payout',
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'minimum_payout_amount' => 1000,
            'reserve_rate' => 10,
            'reserve_days' => 7,
            'payout_delay_days' => 2,
            'charge_bearer' => FeeChargeBearer::Organizer,
            'payout_fee_mode' => FeeCalculationMode::PercentagePlusFixed,
            'payout_fee_percentage' => 1,
            'payout_fee_fixed' => 100,
            'is_active' => true,
            'priority' => 10,
        ]);

        PlatformTransaction::query()->create([
            'tenant_id' => $tenant->getKey(),
            'transaction_reference' => 'TX-AVAILABLE-1',
            'type' => 'public_checkout',
            'direction' => 'credit',
            'status' => 'success',
            'gross_amount' => 10000,
            'fee_amount' => 0,
            'net_amount' => 10000,
            'currency_code' => 'XOF',
            'occurred_at' => now()->subDays(3),
        ]);

        PlatformTransaction::query()->create([
            'tenant_id' => $tenant->getKey(),
            'transaction_reference' => 'TX-AVAILABLE-2',
            'type' => 'public_checkout',
            'direction' => 'credit',
            'status' => 'success',
            'gross_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 5000,
            'currency_code' => 'XOF',
            'occurred_at' => now()->subDay(),
        ]);

        PlatformTransaction::query()->create([
            'tenant_id' => $tenant->getKey(),
            'transaction_reference' => 'TX-REFUND-SETTLED-1',
            'type' => 'refund',
            'direction' => 'debit',
            'status' => 'success',
            'gross_amount' => 3000,
            'fee_amount' => 0,
            'net_amount' => 3000,
            'currency_code' => 'XOF',
            'occurred_at' => now()->subHours(12),
        ]);

        PlatformTransaction::query()->create([
            'tenant_id' => $tenant->getKey(),
            'transaction_reference' => 'TX-REFUND-PENDING-1',
            'type' => 'refund',
            'direction' => 'debit',
            'status' => 'processing',
            'gross_amount' => 1000,
            'fee_amount' => 0,
            'net_amount' => 1000,
            'currency_code' => 'XOF',
            'occurred_at' => now()->subHour(),
        ]);

        Settlement::query()->create([
            'tenant_id' => $tenant->getKey(),
            'reference' => 'SET-001',
            'status' => 'pending',
            'gross_amount' => 2000,
            'fee_amount' => 0,
            'reserve_amount' => 0,
            'payout_fee_amount' => 0,
            'net_amount' => 2000,
            'currency_code' => 'XOF',
        ]);

        $service = app(PayoutPolicyService::class);
        $preview = $service->computePayout($tenant, 10000, 'XOF');
        $balance = $service->availableBalance($tenant, 'XOF');

        $this->assertSame(200, $preview['fee_amount']);
        $this->assertSame(9800, $preview['net_amount']);
        $this->assertSame('XOF', $preview['currency_code']);

        $this->assertSame(7000, $balance['matured_amount']);
        $this->assertSame(10000, $balance['matured_credit_amount']);
        $this->assertSame(3000, $balance['matured_debit_amount']);
        $this->assertSame(1000, $balance['pending_debit_hold_amount']);
        $this->assertSame(1500, $balance['reserve_hold_amount']);
        $this->assertSame(2000, $balance['reserved_settlement_amount']);
        $this->assertSame(2500, $balance['available_amount']);
        $this->assertSame(1000, data_get($balance, 'policy.minimum_payout_amount'));
    }

    private function prepareCentralSchema(): void
    {
        Schema::connection('central')->dropAllTables();

        Schema::connection('central')->create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('UTC');
            $table->string('database_name')->unique();
            $table->string('database_host')->nullable();
            $table->unsignedInteger('database_port')->nullable();
            $table->string('database_username')->nullable();
            $table->text('database_password')->nullable();
            $table->json('database_options')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('central')->create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('mode')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->json('supported_countries')->nullable();
            $table->json('supported_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('gateway_fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('name');
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->string('charge_bearer', 20)->default('organizer');
            $table->string('fee_mode', 40)->default('percentage');
            $table->decimal('percentage_rate', 8, 4)->nullable();
            $table->unsignedBigInteger('fixed_amount')->nullable();
            $table->unsignedBigInteger('cap_amount')->nullable();
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_gross_up_enabled')->default(false);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('module', 80)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('charge_bearer', 20)->default('buyer');
            $table->string('fee_mode', 40)->default('percentage');
            $table->decimal('percentage_rate', 8, 4)->nullable();
            $table->unsignedBigInteger('fixed_amount')->nullable();
            $table->unsignedBigInteger('cap_amount')->nullable();
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('payout_policies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->unsignedBigInteger('minimum_payout_amount')->default(1000);
            $table->decimal('reserve_rate', 8, 4)->default(0);
            $table->unsignedInteger('reserve_days')->default(0);
            $table->unsignedInteger('payout_delay_days')->default(0);
            $table->string('charge_bearer', 20)->default('organizer');
            $table->string('payout_fee_mode', 40)->nullable();
            $table->decimal('payout_fee_percentage', 8, 4)->nullable();
            $table->unsignedBigInteger('payout_fee_fixed')->nullable();
            $table->unsignedBigInteger('payout_fee_cap')->nullable();
            $table->boolean('auto_payout_enabled')->default(false);
            $table->boolean('requires_manual_review')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('transaction_reference')->unique();
            $table->string('type');
            $table->string('direction')->default('credit');
            $table->string('status')->default('pending');
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->bigInteger('gateway_fee_amount')->default(0);
            $table->bigInteger('platform_fee_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('payout_fee_amount')->default(0);
            $table->bigInteger('customer_fee_amount')->default(0);
            $table->bigInteger('absorbed_fee_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->timestamp('occurred_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('payout_policy_id')->nullable()->constrained('payout_policies')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('pending');
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('reserve_amount')->default(0);
            $table->bigInteger('payout_fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->nullable();
            $table->foreignId('tenant_id')->nullable();
            $table->string('event');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();
        });
    }
}
