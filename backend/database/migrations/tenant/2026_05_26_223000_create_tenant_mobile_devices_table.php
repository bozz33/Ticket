<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('mobile_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id')->index();
            $table->string('platform', 32)->index();
            $table->string('push_provider', 32)->default('expo')->index();
            $table->text('push_token');
            $table->string('app_version')->nullable();
            $table->string('device_name')->nullable();
            $table->string('locale')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('mobile_devices');
    }
};
