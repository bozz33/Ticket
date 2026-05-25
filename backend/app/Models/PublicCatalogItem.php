<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicCatalogItem extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'tenant_public_id',
        'tenant_slug',
        'tenant_name',
        'module',
        'item_public_id',
        'item_slug',
        'title',
        'summary',
        'category',
        'city',
        'country_code',
        'currency_code',
        'price_from',
        'is_free',
        'is_featured',
        'likes_count',
        'weekly_likes_count',
        'popularity_score',
        'published_at',
        'starts_at',
        'ends_at',
        'search_text',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'price_from' => 'integer',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'likes_count' => 'integer',
            'weekly_likes_count' => 'integer',
            'popularity_score' => 'integer',
            'published_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
