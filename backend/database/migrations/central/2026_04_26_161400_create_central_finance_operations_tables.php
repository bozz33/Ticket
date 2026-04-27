<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('transaction_reference')->unique();
            $table->string('gateway_reference')->nullable()->index();
            $table->string('type')->index();
            $table->string('direction')->default('credit')->index();
            $table->string('status')->default('pending')->index();
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF')->index();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('payment_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('platform_transaction_id')->nullable()->constrained('platform_transactions')->nullOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('severity')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->string('incident_code')->nullable()->index();
            $table->text('summary');
            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('payout_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft')->index();
            $table->string('currency_code', 3)->default('XOF')->index();
            $table->unsignedInteger('tenant_count')->default(0);
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payout_batch_id')->nullable()->constrained('payout_batches')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft')->index();
            $table->date('period_start')->nullable()->index();
            $table->date('period_end')->nullable()->index();
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('reconciliation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->foreignId('platform_transaction_id')->nullable()->constrained('platform_transactions')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('scope')->default('daily')->index();
            $table->date('period_start')->nullable()->index();
            $table->date('period_end')->nullable()->index();
            $table->unsignedInteger('discrepancies_count')->default(0);
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('financial_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('export_type')->index();
            $table->string('format', 20)->default('csv')->index();
            $table->string('status')->default('pending')->index();
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('scope')->default('platform')->index();
            $table->date('snapshot_date')->index();
            $table->json('metrics');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'scope', 'snapshot_date'], 'kpi_snapshots_unique_scope');
        });

        Schema::connection('central')->create('gateway_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('platform_transaction_id')->nullable()->constrained('platform_transactions')->nullOnDelete();
            $table->string('event_name')->index();
            $table->string('external_id')->nullable()->index();
            $table->text('signature')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('received')->index();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->unsignedInteger('attempt_count')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('gateway_webhook_logs');
        Schema::connection('central')->dropIfExists('kpi_snapshots');
        Schema::connection('central')->dropIfExists('financial_exports');
        Schema::connection('central')->dropIfExists('reconciliation_logs');
        Schema::connection('central')->dropIfExists('settlements');
        Schema::connection('central')->dropIfExists('payout_batches');
        Schema::connection('central')->dropIfExists('payment_incidents');
        Schema::connection('central')->dropIfExists('platform_transactions');
    }
};
