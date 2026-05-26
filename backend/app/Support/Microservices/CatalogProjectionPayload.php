<?php

namespace App\Support\Microservices;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class CatalogProjectionPayload
{
    public static function fromContent(Model $content, string $module, array $overrides = []): array
    {
        $title = (string) ($overrides['title'] ?? $content->getAttribute('title') ?? $content->getAttribute('name') ?? '');
        $summary = $overrides['summary'] ?? $content->getAttribute('summary');
        $description = $content->getAttribute('description');
        $startsAt = $overrides['starts_at'] ?? $content->getAttribute('starts_at') ?? $content->getAttribute('application_opens_at');
        $endsAt = $overrides['ends_at'] ?? $content->getAttribute('ends_at') ?? $content->getAttribute('application_closes_at');
        $priceFrom = (int) ($overrides['price_from'] ?? $content->getAttribute('price_amount') ?? 0);

        return array_replace([
            'tenant_id' => (string) tenant('id'),
            'tenant_public_id' => (string) tenant('id'),
            'tenant_slug' => (string) tenant('id'),
            'tenant_name' => (string) tenant('id'),
            'module' => $module,
            'item_public_id' => (string) $content->getAttribute('public_id'),
            'item_slug' => (string) $content->getAttribute('slug'),
            'title' => $title,
            'summary' => $summary,
            'category' => $content->getRelationValue('category')?->name,
            'city' => null,
            'country_code' => $content->getAttribute('country_code'),
            'currency_code' => $content->getAttribute('currency_code'),
            'price_from' => $priceFrom,
            'is_free' => $priceFrom <= 0,
            'is_featured' => false,
            'likes_count' => 0,
            'weekly_likes_count' => 0,
            'popularity_score' => 0,
            'published_at' => self::isoDate($content->getAttribute('published_at')),
            'starts_at' => self::isoDate($startsAt),
            'ends_at' => self::isoDate($endsAt),
            'search_text' => trim($title.' '.($summary ?? '').' '.($description ?? '')),
            'payload' => array_filter([
                'public_id' => $content->getAttribute('public_id'),
                'slug' => $content->getAttribute('slug'),
                'title' => $content->getAttribute('title'),
                'name' => $content->getAttribute('name'),
                'summary' => $summary,
                'description' => $description,
                'cover_image_url' => $content->getAttribute('cover_image_url'),
                'venue_name' => $content->getAttribute('venue_name'),
                'venue_address' => $content->getAttribute('venue_address'),
                'target_amount' => $content->getAttribute('target_amount'),
                'raised_amount' => $content->getAttribute('raised_amount'),
                'quantity_available' => $content->getAttribute('quantity_available'),
            ], fn ($value): bool => $value !== null && $value !== ''),
        ], $overrides);
    }

    public static function isoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }
}
