<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    /**
     * Add soft-delete support to financial and access models.
     *
     * Orders and receipts have legal/accounting significance and must never be
     * hard-deleted. AccessPasses and EventTickets are linked to financial records
     * and should follow the same retention policy.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::connection('tenant')->table('event_tickets', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::connection('tenant')->table('receipts', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::connection('tenant')->table('event_tickets', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
