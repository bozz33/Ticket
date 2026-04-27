<?php

use App\Enums\CategoryScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $categories = [
            ['name' => 'Concert', 'slug' => 'concert'],
            ['name' => 'Culture', 'slug' => 'culture'],
            ['name' => 'Formation', 'slug' => 'formation'],
            ['name' => 'Soirée', 'slug' => 'soiree'],
            ['name' => 'Tourisme', 'slug' => 'tourisme'],
            ['name' => 'Sport', 'slug' => 'sport'],
            ['name' => 'Festival', 'slug' => 'festival'],
            ['name' => 'Science', 'slug' => 'science'],
            ['name' => 'Religieux', 'slug' => 'religieux'],
            ['name' => 'Gastronomie', 'slug' => 'gastronomie'],
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Autre', 'slug' => 'autre'],
        ];

        $rows = array_map(function (array $category, int $index) use ($now): array {
            return [
                'public_id' => (string) Str::uuid(),
                'parent_id' => null,
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => null,
                'module_scope' => CategoryScope::Event->value,
                'sort_order' => $index + 1,
                'is_active' => true,
                'meta' => json_encode([
                    'source' => 'tikerama_public_events_page',
                    'tab' => 'events',
                ], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $categories, array_keys($categories));

        $connection->table('categories')->upsert(
            $rows,
            ['slug'],
            ['name', 'description', 'module_scope', 'sort_order', 'is_active', 'meta', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::connection('central')->table('categories')->whereIn('slug', [
            'concert',
            'culture',
            'formation',
            'soiree',
            'tourisme',
            'sport',
            'festival',
            'science',
            'religieux',
            'gastronomie',
            'business',
            'autre',
        ])->delete();
    }
};
