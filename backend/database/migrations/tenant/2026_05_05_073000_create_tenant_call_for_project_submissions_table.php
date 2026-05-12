<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('call_for_project_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('call_for_project_id')->constrained('call_for_projects')->cascadeOnDelete();
            $table->string('status')->default('submitted')->index();
            $table->string('applicant_name')->nullable();
            $table->string('applicant_email')->nullable()->index();
            $table->string('phone_country_code', 10)->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('city_name')->nullable()->index();
            $table->json('answers')->nullable();
            $table->json('files')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('call_for_project_submissions');
    }
};
