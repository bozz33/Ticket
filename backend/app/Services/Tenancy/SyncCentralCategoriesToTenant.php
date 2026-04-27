<?php

namespace App\Services\Tenancy;

use App\Models\Category;
use App\Models\CentralCategory;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class SyncCentralCategoriesToTenant
{
    public function handle(Tenant $tenant): array
    {
        $syncedAt = now();
        $centralCategories = CentralCategory::query()
            ->orderByRaw('parent_id is null desc')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $connectionName = config('ticket.tenant_connection', 'tenant');

        $result = $tenant->run(function () use ($connectionName, $centralCategories, $syncedAt): array {
            return DB::connection($connectionName)->transaction(function () use ($centralCategories, $syncedAt): array {
                $centralIds = $centralCategories->pluck('id')->all();

                $removedCount = Category::query()
                    ->whereNotNull('synced_from_category_id')
                    ->when(
                        $centralIds !== [],
                        fn ($query) => $query->whereNotIn('synced_from_category_id', $centralIds)
                    )
                    ->when(
                        $centralIds === [],
                        fn ($query) => $query
                    )
                    ->delete();

                $mapping = [];

                foreach ($centralCategories as $centralCategory) {
                    $tenantCategory = Category::query()->updateOrCreate(
                        ['synced_from_category_id' => $centralCategory->id],
                        [
                            'public_id' => $centralCategory->public_id,
                            'parent_id' => null,
                            'name' => $centralCategory->name,
                            'slug' => $centralCategory->slug,
                            'description' => $centralCategory->description,
                            'module_scope' => $centralCategory->module_scope,
                            'sort_order' => $centralCategory->sort_order,
                            'is_active' => $centralCategory->is_active,
                            'sync_checksum' => $this->checksum($centralCategory),
                            'last_synced_at' => $syncedAt,
                            'meta' => $centralCategory->meta ?? [],
                        ],
                    );

                    $mapping[$centralCategory->id] = $tenantCategory->id;
                }

                foreach ($centralCategories as $centralCategory) {
                    Category::query()
                        ->where('synced_from_category_id', $centralCategory->id)
                        ->update([
                            'parent_id' => $centralCategory->parent_id !== null
                                ? ($mapping[$centralCategory->parent_id] ?? null)
                                : null,
                        ]);
                }

                return [
                    'synced_count' => count($mapping),
                    'removed_count' => $removedCount,
                ];
            });
        });

        return [
            'tenant' => $tenant->fresh(['profile', 'domains']),
            'synced_at' => $syncedAt->toIso8601String(),
            'synced_count' => $result['synced_count'],
            'removed_count' => $result['removed_count'],
        ];
    }

    protected function checksum(CentralCategory $centralCategory): string
    {
        return md5(json_encode([
            'public_id' => $centralCategory->public_id,
            'parent_id' => $centralCategory->parent_id,
            'name' => $centralCategory->name,
            'slug' => $centralCategory->slug,
            'description' => $centralCategory->description,
            'module_scope' => $centralCategory->module_scope?->value,
            'sort_order' => $centralCategory->sort_order,
            'is_active' => $centralCategory->is_active,
            'meta' => $centralCategory->meta ?? [],
        ], JSON_THROW_ON_ERROR));
    }
}
