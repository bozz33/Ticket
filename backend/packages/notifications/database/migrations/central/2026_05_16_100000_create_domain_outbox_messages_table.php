<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('ticket.central_connection', 'central'))->create('domain_outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('type')->index();
            $table->string('aggregate_type')->nullable()->index();
            $table->string('aggregate_id')->nullable()->index();
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'domain_outbox_pending_index');
            $table->index(['aggregate_type', 'aggregate_id'], 'domain_outbox_aggregate_index');
        });
    }

    public function down(): void
    {
        Schema::connection(config('ticket.central_connection', 'central'))->dropIfExists('domain_outbox_messages');
    }
};
