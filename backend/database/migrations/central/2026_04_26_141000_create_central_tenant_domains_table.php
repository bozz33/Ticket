<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(true)->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_domains');
    }
};
