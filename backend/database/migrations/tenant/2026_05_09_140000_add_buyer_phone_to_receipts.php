<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('receipts', 'buyer_phone')) {
                $table->string('buyer_phone', 30)->nullable()->after('buyer_email');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('receipts', 'buyer_phone')) {
                $table->dropColumn('buyer_phone');
            }
        });
    }
};
