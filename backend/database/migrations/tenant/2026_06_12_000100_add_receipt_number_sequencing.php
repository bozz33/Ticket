<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('receipt_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            $table->string('receipt_number')->nullable()->unique()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            $table->dropColumn('receipt_number');
        });

        Schema::connection('tenant')->dropIfExists('receipt_number_sequences');
    }
};
