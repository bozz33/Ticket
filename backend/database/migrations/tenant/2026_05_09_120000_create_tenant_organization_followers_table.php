<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('organization_followers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_profile_id')->constrained('organization_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_profile_id', 'user_id'], 'organization_followers_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('organization_followers');
    }
};
