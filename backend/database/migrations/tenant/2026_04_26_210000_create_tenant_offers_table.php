<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('offerable_type');
            $table->unsignedBigInteger('offerable_id');
            $table->string('offer_type', 100)->default('standard')->index();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->string('currency_code', 3)->nullable()->index();
            $table->unsignedInteger('quantity_total')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('min_per_order')->default(1);
            $table->unsignedInteger('max_per_order')->nullable();
            $table->timestamp('sales_start_at')->nullable()->index();
            $table->timestamp('sales_end_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['offerable_type', 'offerable_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('offers');
    }
};
