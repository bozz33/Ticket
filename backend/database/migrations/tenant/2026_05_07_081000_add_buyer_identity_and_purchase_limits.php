<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('offers', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('offers', 'max_per_account')) {
                $table->unsignedInteger('max_per_account')->nullable()->after('max_per_order');
            }
        });

        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('orders', 'buyer_user_id')) {
                $table->foreignId('buyer_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('receipts', 'buyer_user_id')) {
                $table->foreignId('buyer_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('access_passes', 'holder_user_id')) {
                $table->foreignId('holder_user_id')->nullable()->after('offer_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('access_passes', 'holder_user_id')) {
                $table->dropConstrainedForeignId('holder_user_id');
            }
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('receipts', 'buyer_user_id')) {
                $table->dropConstrainedForeignId('buyer_user_id');
            }
        });

        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('orders', 'buyer_user_id')) {
                $table->dropConstrainedForeignId('buyer_user_id');
            }
        });

        Schema::connection('tenant')->table('offers', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('offers', 'max_per_account')) {
                $table->dropColumn('max_per_account');
            }
        });
    }
};
