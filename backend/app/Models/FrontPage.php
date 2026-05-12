<?php

namespace App\Models;

use App\Enums\FrontPageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrontPage extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'key',
        'title',
        'route_path',
        'slug',
        'template',
        'status',
        'is_active',
        'show_in_sitemap',
        'seo_title',
        'seo_description',
        'seo_image_url',
        'meta',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FrontPageStatus::class,
            'is_active' => 'boolean',
            'show_in_sitemap' => 'boolean',
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FrontPageSection::class)->orderBy('sort_order')->orderBy('id');
    }
}
