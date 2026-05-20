<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('event_ticket_categories')) {
            Schema::connection('tenant')->create('event_ticket_categories', function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->string('name');
                $table->string('code')->unique();
                $table->longText('description')->nullable();
                $table->string('color', 40)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (
            Schema::connection('tenant')->hasTable('event_tickets')
            && ! Schema::connection('tenant')->hasColumn('event_tickets', 'ticket_category_id')
        ) {
            Schema::connection('tenant')->table('event_tickets', function (Blueprint $table): void {
                $table->foreignId('ticket_category_id')
                    ->nullable()
                    ->after('event_id')
                    ->constrained('event_ticket_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::connection('tenant')->hasTable('event_tickets')
            && Schema::connection('tenant')->hasColumn('event_tickets', 'ticket_category_id')
        ) {
            Schema::connection('tenant')->table('event_tickets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('ticket_category_id');
            });
        }

        Schema::connection('tenant')->dropIfExists('event_ticket_categories');
    }
};
