<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CallForProject extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected static function booted(): void
    {
        static::saving(function (self $callForProject): void {
            if (blank($callForProject->published_at) && $callForProject->is_active && (string) $callForProject->public_status_code === 'published') {
                $callForProject->published_at = now();
            }
        });
    }

    protected $fillable = [
        'public_id',
        'category_id',
        'organization_profile_id',
        'public_status_code',
        'title',
        'slug',
        'summary',
        'description',
        'application_opens_at',
        'application_closes_at',
        'is_active',
        'published_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'application_opens_at' => 'datetime',
            'application_closes_at' => 'datetime',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function organizationProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizationProfile::class);
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CallForProjectSubmission::class)->latest();
    }
}
