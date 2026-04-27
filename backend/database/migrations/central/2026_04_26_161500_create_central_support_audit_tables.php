<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('subject');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->string('priority')->default('normal')->index();
            $table->string('category')->nullable()->index();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('incident_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('payment_incident_id')->nullable()->constrained('payment_incidents')->nullOnDelete();
            $table->foreignId('platform_support_ticket_id')->nullable()->constrained('platform_support_tickets')->nullOnDelete();
            $table->string('title');
            $table->string('severity')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->string('incident_type')->nullable()->index();
            $table->text('summary')->nullable();
            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id')->nullable()->index();
            $table->string('subject_label')->nullable()->index();
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_audit_logs');
        Schema::connection('central')->dropIfExists('incident_logs');
        Schema::connection('central')->dropIfExists('platform_support_tickets');
    }
};
