<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reference')->unique();
            $table->string('transaction_reference')->unique()->index();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF')->index();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable()->index();
            $table->string('buyer_phone', 30)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('orders');
    }
};
