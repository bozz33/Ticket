<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('commercial_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 80)->unique();
            $table->string('monetization_mode', 30)->default('free')->index();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->unsignedInteger('flat_fee_amount')->nullable();
            $table->string('currency_code', 3)->default('XOF');
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('commercial_policies');
    }
};
