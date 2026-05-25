<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CrowdfundingCampaign extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected static function booted(): void
    {
        static::saving(function (self $campaign): void {
            if ($campaign->starts_at && $campaign->ends_at && $campaign->starts_at->greaterThan($campaign->ends_at)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'La fin de collecte doit être après le début de collecte.',
                ]);
            }

            $eventAt = $campaign->eventDateFromMeta();

            if ($eventAt && $campaign->starts_at && $campaign->starts_at->greaterThan($eventAt)) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Le début de collecte ne peut pas dépasser la date et heure de l’activité.',
                ]);
            }

            if ($eventAt && $campaign->ends_at && $campaign->ends_at->greaterThan($eventAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'La fin de collecte ne peut pas dépasser la date et heure de l’activité.',
                ]);
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
        'currency_code',
        'target_amount',
        'raised_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'published_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'raised_amount' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function contributions(): HasMany
    {
        return $this->hasMany(CrowdfundingContribution::class);
    }

    private function eventDateFromMeta(): ?Carbon
    {
        $value = is_array($this->meta) ? ($this->meta['event_at'] ?? null) : null;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
