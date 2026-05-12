<?php

namespace App\Models;

use App\Enums\FrontPageSectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontPageSection extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'front_page_id',
        'key',
        'type',
        'title',
        'eyebrow',
        'body',
        'image_url',
        'primary_cta_label',
        'primary_cta_url',
        'secondary_cta_label',
        'secondary_cta_url',
        'sort_order',
        'is_active',
        'items',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => FrontPageSectionType::class,
            'is_active' => 'boolean',
            'items' => 'array',
            'settings' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FrontPage::class, 'front_page_id');
    }
}
