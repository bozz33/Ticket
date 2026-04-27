<?php

namespace App\Services\Tenancy;

use App\Models\CentralTag;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class SyncCentralTagsToTenant
{
    public function handle(Tenant $tenant): array
    {
        $syncedAt = now();
        $centralTags = CentralTag::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $connectionName = config('ticket.tenant_connection', 'tenant');

        $result = $tenant->run(function () use ($connectionName, $centralTags, $syncedAt): array {
            return DB::connection($connectionName)->transaction(function () use ($centralTags, $syncedAt): array {
                $centralIds = $centralTags->pluck('id')->all();

                $removedCount = Tag::query()
                    ->whereNotNull('synced_from_tag_id')
                    ->when(
                        $centralIds !== [],
                        fn ($query) => $query->whereNotIn('synced_from_tag_id', $centralIds)
                    )
                    ->delete();

                $syncedCount = 0;

                foreach ($centralTags as $centralTag) {
                    Tag::query()->updateOrCreate(
                        ['synced_from_tag_id' => $centralTag->id],
                        [
                            'public_id' => $centralTag->public_id,
                            'name' => $centralTag->name,
                            'slug' => $centralTag->slug,
                            'description' => $centralTag->description,
                            'module_scope' => $centralTag->module_scope,
                            'sort_order' => $centralTag->sort_order,
                            'is_active' => $centralTag->is_active,
                            'sync_checksum' => $this->checksum($centralTag),
                            'last_synced_at' => $syncedAt,
                            'meta' => $centralTag->meta ?? [],
                        ],
                    );

                    $syncedCount++;
                }

                return [
                    'synced_count' => $syncedCount,
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

    protected function checksum(CentralTag $centralTag): string
    {
        return md5(json_encode([
            'public_id' => $centralTag->public_id,
            'name' => $centralTag->name,
            'slug' => $centralTag->slug,
            'description' => $centralTag->description,
            'module_scope' => $centralTag->module_scope?->value,
            'sort_order' => $centralTag->sort_order,
            'is_active' => $centralTag->is_active,
            'meta' => $centralTag->meta ?? [],
        ], JSON_THROW_ON_ERROR));
    }
}
