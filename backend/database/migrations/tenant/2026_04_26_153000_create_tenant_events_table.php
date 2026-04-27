<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_profile_id')->nullable()->constrained('organization_profiles')->nullOnDelete();
            $table->string('public_status_code')->default('draft')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('currency_code', 3)->nullable()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('events');
    }
};
