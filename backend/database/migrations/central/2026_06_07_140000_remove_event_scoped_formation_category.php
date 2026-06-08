<?php

use App\Enums\CategoryScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * "Formation" is now a first-class module (Training) with its own training-scoped
     * categories, so the legacy event-scoped "Formation" category is redundant and
     * confusing in the event form. Remove it from the event scope. Events that still
     * reference it are nulled automatically (events.category_id is nullOnDelete) via the
     * tenant category sync.
     */
    public function up(): void
    {
        DB::connection('central')->table('categories')
            ->where('slug', 'formation')
            ->where('module_scope', CategoryScope::Event->value)
            ->delete();
    }

    public function down(): void
    {
        DB::connection('central')->table('categories')->updateOrInsert(
            ['slug' => 'formation'],
            [
                'public_id' => (string) Str::uuid(),
                'parent_id' => null,
                'name' => 'Formation',
                'description' => null,
                'module_scope' => CategoryScope::Event->value,
                'sort_order' => 3,
                'is_active' => true,
                'meta' => json_encode(['source' => 'tikerama_public_events_page', 'tab' => 'events'], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
