<?php

namespace Ticket\PublicCatalog\Application;

use App\Models\PublicCatalogItem;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

class PublicCatalogEngagementUpdater
{
    public function syncContentLikeCounts(?Tenant $tenant, string $module, string $slug, int $likesCount, int $weeklyLikesCount): void
    {
        if ($tenant === null || $module === '' || $slug === '' || ! $this->tableReady()) {
            return;
        }

        $item = PublicCatalogItem::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('module', $module)
            ->where('item_slug', $slug)
            ->first();

        if (! $item) {
            return;
        }

        $payload = (array) $item->payload;
        $payload['likesCount'] = max(0, $likesCount);
        $payload['weeklyLikesCount'] = max(0, $weeklyLikesCount);

        $item->forceFill([
            'likes_count' => max(0, $likesCount),
            'weekly_likes_count' => max(0, $weeklyLikesCount),
            'popularity_score' => $this->popularityScore($payload),
            'payload' => $payload,
        ])->save();
    }

    private function popularityScore(array $payload): int
    {
        $score = ((int) ($payload['weeklyLikesCount'] ?? 0) * 100000)
            + (int) ($payload['likesCount'] ?? 0);

        if ((bool) ($payload['popular'] ?? false)) {
            $score += 1000;
        }

        if ((bool) ($payload['featured'] ?? false)) {
            $score += 250;
        }

        return $score;
    }

    private function tableReady(): bool
    {
        try {
            $connection = config('ticket.central_connection', 'central');

            return Schema::connection($connection)->hasTable('public_catalog_items')
                && Schema::connection($connection)->hasColumn('public_catalog_items', 'likes_count')
                && Schema::connection($connection)->hasColumn('public_catalog_items', 'weekly_likes_count');
        } catch (\Throwable) {
            return false;
        }
    }
}
