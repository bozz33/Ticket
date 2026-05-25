<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('ticket.central_connection', 'central');

        Schema::connection($connection)->table('public_catalog_items', function (Blueprint $table) use ($connection): void {
            if (! Schema::connection($connection)->hasColumn('public_catalog_items', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('is_featured')->index();
            }

            if (! Schema::connection($connection)->hasColumn('public_catalog_items', 'weekly_likes_count')) {
                $table->unsignedInteger('weekly_likes_count')->default(0)->after('likes_count')->index();
            }
        });

        Schema::connection($connection)->table('public_catalog_items', function (Blueprint $table): void {
            $table->index(['module', 'weekly_likes_count', 'likes_count', 'published_at'], 'public_catalog_items_weekly_likes_index');
        });
    }

    public function down(): void
    {
        $connection = config('ticket.central_connection', 'central');

        Schema::connection($connection)->table('public_catalog_items', function (Blueprint $table) use ($connection): void {
            $table->dropIndex('public_catalog_items_weekly_likes_index');

            if (Schema::connection($connection)->hasColumn('public_catalog_items', 'weekly_likes_count')) {
                $table->dropColumn('weekly_likes_count');
            }

            if (Schema::connection($connection)->hasColumn('public_catalog_items', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
        });
    }
};
