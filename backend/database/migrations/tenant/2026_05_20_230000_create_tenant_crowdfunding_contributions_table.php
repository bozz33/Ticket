<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('crowdfunding_contributions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('crowdfunding_campaign_id')->constrained('crowdfunding_campaigns')->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_reference')->nullable()->index();
            $table->string('contributor_name')->nullable();
            $table->string('contributor_email')->nullable()->index();
            $table->string('contributor_phone', 50)->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->string('currency_code', 3)->index();
            $table->string('status', 40)->default('confirmed')->index();
            $table->boolean('is_anonymous')->default(false)->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['transaction_reference', 'order_id']);
            $table->index(['crowdfunding_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('crowdfunding_contributions');
    }
};
