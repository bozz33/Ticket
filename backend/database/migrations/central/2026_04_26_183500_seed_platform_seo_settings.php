<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $items = [
            [
                'group' => 'seo',
                'key' => 'meta_title',
                'value' => json_encode(['default' => 'Ticket - Plateforme multi-tenant événementielle'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'meta_description',
                'value' => json_encode(['default' => 'Ticket centralise la gestion multi-tenant, les modules, les paiements et l’administration des organisations.'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'meta_keywords',
                'value' => json_encode(['default' => 'ticketing,event,tenant,saas,platform'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'robots',
                'value' => json_encode(['default' => 'index,follow'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'open_graph',
                'value' => json_encode(['site_name' => 'Ticket', 'type' => 'website'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'twitter',
                'value' => json_encode(['card' => 'summary_large_image'], JSON_THROW_ON_ERROR),
                'type' => 'json',
                'is_public' => true,
            ],
        ];

        foreach ($items as $item) {
            $connection->table('platform_settings')->updateOrInsert(
                ['key' => $item['key']],
                [
                    'group' => $item['group'],
                    'value' => $item['value'],
                    'type' => $item['type'],
                    'is_public' => $item['is_public'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::connection('central')
            ->table('platform_settings')
            ->where('group', 'seo')
            ->whereIn('key', ['meta_title', 'meta_description', 'meta_keywords', 'robots', 'open_graph', 'twitter'])
            ->delete();
    }
};
