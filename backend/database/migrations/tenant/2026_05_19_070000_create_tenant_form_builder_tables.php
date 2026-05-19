<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('form_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->nullableMorphs('owner');
            $table->string('name');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('submit_label')->default('Envoyer');
            $table->longText('success_message')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->json('schema')->nullable();
            $table->json('validation_schema')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::connection('tenant')->create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->foreignId('submitter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('submitted')->index();
            $table->json('data')->nullable();
            $table->json('files')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('form_submissions');
        Schema::connection('tenant')->dropIfExists('form_definitions');
    }
};
