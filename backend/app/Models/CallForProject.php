<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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

            if ($callForProject->application_opens_at && $callForProject->application_closes_at && $callForProject->application_opens_at->greaterThan($callForProject->application_closes_at)) {
                throw ValidationException::withMessages([
                    'application_closes_at' => 'La clôture des candidatures doit être après l’ouverture.',
                ]);
            }

            $eventAt = $callForProject->eventDateFromMeta();

            if ($eventAt && $callForProject->application_opens_at && $callForProject->application_opens_at->greaterThan($eventAt)) {
                throw ValidationException::withMessages([
                    'application_opens_at' => 'L’ouverture des candidatures ne peut pas dépasser la date et heure de l’activité.',
                ]);
            }

            if ($eventAt && $callForProject->application_closes_at && $callForProject->application_closes_at->greaterThan($eventAt)) {
                throw ValidationException::withMessages([
                    'application_closes_at' => 'La clôture des candidatures ne peut pas dépasser la date et heure de l’activité.',
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

    public function formDefinition(): MorphOne
    {
        return $this->morphOne(FormDefinition::class, 'owner')->latestOfMany();
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
