<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'organization_profile_id',
        'category_id',
        'public_status_code',
        'title',
        'slug',
        'summary',
        'description',
        'timezone',
        'currency_code',
        'country_code',
        'city_id',
        'venue_name',
        'venue_address',
        'cover_image_url',
        'is_active',
        'published_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organizationProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizationProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(EventDate::class)->orderBy('sort_order')->orderBy('starts_at');
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable')->orderBy('sort_order');
    }
}
