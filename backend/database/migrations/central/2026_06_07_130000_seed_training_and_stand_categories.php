<?php

use App\Enums\CategoryScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Training and Stand are first-class public modules (PublicCatalogModules maps
     * formations => Training, stands => Stand) but had no category taxonomy of their own —
     * every seeded category was scoped to "event". This seeds the missing training/stand
     * scoped categories so their tenant resources expose a meaningful classification.
     */
    public function up(): void
    {
        $now = now();

        $training = [
            ['name' => 'Atelier', 'slug' => 'formation-atelier'],
            ['name' => 'Masterclass', 'slug' => 'formation-masterclass'],
            ['name' => 'Séminaire', 'slug' => 'formation-seminaire'],
            ['name' => 'Bootcamp', 'slug' => 'formation-bootcamp'],
            ['name' => 'Certification', 'slug' => 'formation-certification'],
            ['name' => 'Cours en ligne', 'slug' => 'formation-cours-en-ligne'],
        ];

        $stand = [
            ['name' => 'Salon professionnel', 'slug' => 'stand-salon-professionnel'],
            ['name' => 'Foire', 'slug' => 'stand-foire'],
            ['name' => 'Exposition', 'slug' => 'stand-exposition'],
            ['name' => 'Showcase', 'slug' => 'stand-showcase'],
            ['name' => 'Marché', 'slug' => 'stand-marche'],
        ];

        $this->upsertScope($training, CategoryScope::Training->value, $now);
        $this->upsertScope($stand, CategoryScope::Stand->value, $now);
    }

    public function down(): void
    {
        DB::connection('central')->table('categories')->whereIn('slug', [
            'formation-atelier', 'formation-masterclass', 'formation-seminaire',
            'formation-bootcamp', 'formation-certification', 'formation-cours-en-ligne',
            'stand-salon-professionnel', 'stand-foire', 'stand-exposition',
            'stand-showcase', 'stand-marche',
        ])->delete();
    }

    private function upsertScope(array $categories, string $scope, $now): void
    {
        $rows = array_map(static function (array $category, int $index) use ($scope, $now): array {
            return [
                'public_id' => (string) Str::uuid(),
                'parent_id' => null,
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => null,
                'module_scope' => $scope,
                'sort_order' => $index + 1,
                'is_active' => true,
                'meta' => json_encode(['source' => 'module_taxonomy_alignment', 'tab' => $scope], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $categories, array_keys($categories));

        DB::connection('central')->table('categories')->upsert(
            $rows,
            ['slug'],
            ['name', 'description', 'module_scope', 'sort_order', 'is_active', 'meta', 'updated_at']
        );
    }
};
