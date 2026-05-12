<?php

use App\Enums\RefundFeeBehavior;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('gateway_fee_rules', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('gateway_fee_rules', 'refund_behavior')) {
                $table->string('refund_behavior', 30)
                    ->default(RefundFeeBehavior::NonRefundable->value)
                    ->after('vat_rate');
            }
        });

        Schema::connection('central')->table('platform_fee_rules', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('platform_fee_rules', 'refund_behavior')) {
                $table->string('refund_behavior', 30)
                    ->default(RefundFeeBehavior::Refundable->value)
                    ->after('vat_rate');
            }
        });

        Schema::connection('central')->create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('platform_transaction_id')->constrained('platform_transactions')->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('gateway_refund_id')->nullable()->index();
            $table->string('gateway_refund_reference')->nullable()->index();
            $table->string('status', 30)->index();
            $table->string('gateway_status', 60)->nullable()->index();
            $table->string('reason_code', 80)->nullable()->index();
            $table->text('reason')->nullable();
            $table->bigInteger('amount_requested')->default(0);
            $table->bigInteger('amount_refunded_to_buyer')->default(0);
            $table->bigInteger('organizer_reversal_amount')->default(0);
            $table->bigInteger('platform_absorption_amount')->default(0);
            $table->bigInteger('gateway_fee_retained')->default(0);
            $table->bigInteger('platform_fee_retained')->default(0);
            $table->bigInteger('tax_retained')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->timestamp('processed_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->timestamps();
        });

        DB::connection('central')->table('gateway_fee_rules')
            ->whereNull('refund_behavior')
            ->update(['refund_behavior' => RefundFeeBehavior::NonRefundable->value]);

        DB::connection('central')->table('platform_fee_rules')
            ->whereNull('refund_behavior')
            ->update(['refund_behavior' => RefundFeeBehavior::Refundable->value]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('refunds');

        Schema::connection('central')->table('platform_fee_rules', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('platform_fee_rules', 'refund_behavior')) {
                $table->dropColumn('refund_behavior');
            }
        });

        Schema::connection('central')->table('gateway_fee_rules', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('gateway_fee_rules', 'refund_behavior')) {
                $table->dropColumn('refund_behavior');
            }
        });
    }
};
