<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        $connection = DB::connection('central');
        $now = now();
        $existing = $connection->table('platform_settings')->where('key', 'seo.social_links')->first();

        $connection->table('platform_settings')->updateOrInsert(
            ['key' => 'seo.social_links'],
            [
                'group' => 'seo',
                'value' => json_encode(['social_links' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
                'created_at' => $existing?->created_at ?? $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        DB::connection('central')->table('platform_settings')->where('key', 'seo.social_links')->delete();
    }
};
