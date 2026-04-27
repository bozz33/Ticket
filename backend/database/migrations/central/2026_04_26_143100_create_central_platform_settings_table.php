<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->nullable()->index();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('json');
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_settings');
    }
};
