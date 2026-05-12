<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('front_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('route_path')->unique();
            $table->string('slug')->unique();
            $table->string('template')->default('content_page');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_sitemap')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection('central')->create('front_page_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('front_page_id')->constrained('front_pages')->cascadeOnDelete();
            $table->string('key');
            $table->string('type')->index();
            $table->string('title')->nullable();
            $table->string('eyebrow')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('items')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['front_page_id', 'key']);
        });

        Schema::connection('central')->create('front_menus', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('location')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('front_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('front_menu_id')->constrained('front_menus')->cascadeOnDelete();
            $table->string('label');
            $table->string('href');
            $table->string('target')->default('_self');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('front_menu_items');
        Schema::connection('central')->dropIfExists('front_menus');
        Schema::connection('central')->dropIfExists('front_page_sections');
        Schema::connection('central')->dropIfExists('front_pages');
    }
};
