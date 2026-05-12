<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('orders', 'subtotal_amount')) {
                $table->unsignedBigInteger('subtotal_amount')->default(0)->after('unit_amount');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'customer_fee_amount')) {
                $table->unsignedBigInteger('customer_fee_amount')->default(0)->after('subtotal_amount');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'gateway_fee_amount_snapshot')) {
                $table->unsignedBigInteger('gateway_fee_amount_snapshot')->default(0)->after('customer_fee_amount');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'platform_fee_amount_snapshot')) {
                $table->unsignedBigInteger('platform_fee_amount_snapshot')->default(0)->after('gateway_fee_amount_snapshot');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'tax_amount_snapshot')) {
                $table->unsignedBigInteger('tax_amount_snapshot')->default(0)->after('platform_fee_amount_snapshot');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'organizer_net_amount_snapshot')) {
                $table->unsignedBigInteger('organizer_net_amount_snapshot')->default(0)->after('tax_amount_snapshot');
            }

            if (! Schema::connection('tenant')->hasColumn('orders', 'pricing_snapshot')) {
                $table->json('pricing_snapshot')->nullable()->after('meta');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            foreach ([
                'subtotal_amount',
                'customer_fee_amount',
                'gateway_fee_amount_snapshot',
                'platform_fee_amount_snapshot',
                'tax_amount_snapshot',
                'organizer_net_amount_snapshot',
                'pricing_snapshot',
            ] as $column) {
                if (Schema::connection('tenant')->hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
