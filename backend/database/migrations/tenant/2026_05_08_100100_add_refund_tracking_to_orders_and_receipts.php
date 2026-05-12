<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('orders', 'refunded_amount')) {
                $table->bigInteger('refunded_amount')->default(0)->after('total_amount');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('currency_code');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'refund_reference')) {
                $table->string('refund_reference')->nullable()->after('refunded_at');
            }
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('receipts', 'refunded_amount')) {
                $table->bigInteger('refunded_amount')->default(0)->after('total_amount');
            }

            if (! Schema::connection('tenant')->hasColumn('receipts', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            foreach (['refunded_amount', 'refunded_at'] as $column) {
                if (Schema::connection('tenant')->hasColumn('receipts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            foreach (['refunded_amount', 'refunded_at', 'refund_reference'] as $column) {
                if (Schema::connection('tenant')->hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
