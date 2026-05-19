<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('ticket.central_connection', 'central'))->create('public_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('tenant_public_id')->index();
            $table->string('tenant_slug')->index();
            $table->string('tenant_name');
            $table->string('module')->index();
            $table->string('item_public_id');
            $table->string('item_slug')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->string('country_code')->nullable()->index();
            $table->string('currency_code', 8)->nullable();
            $table->unsignedBigInteger('price_from')->default(0)->index();
            $table->boolean('is_free')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('popularity_score')->default(0)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->longText('search_text');
            $table->json('payload');
            $table->timestamps();

            $table->unique(['tenant_id', 'module', 'item_public_id'], 'public_catalog_items_unique_item');
            $table->index(['module', 'published_at'], 'public_catalog_items_module_published_index');
            $table->index(['module', 'category', 'city'], 'public_catalog_items_filter_index');
            $table->index(['tenant_slug', 'module', 'item_slug'], 'public_catalog_items_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::connection(config('ticket.central_connection', 'central'))->dropIfExists('public_catalog_items');
    }
};
