<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Offer extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'offerable_type',
        'offerable_id',
        'offer_type',
        'name',
        'code',
        'description',
        'price_amount',
        'currency_code',
        'quantity_total',
        'quantity_sold',
        'min_per_order',
        'max_per_order',
        'max_per_account',
        'sales_start_at',
        'sales_end_at',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'quantity_total' => 'integer',
            'quantity_sold' => 'integer',
            'min_per_order' => 'integer',
            'max_per_order' => 'integer',
            'max_per_account' => 'integer',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $offer): void {
            if ($offer->sales_start_at && $offer->sales_end_at && $offer->sales_start_at->greaterThan($offer->sales_end_at)) {
                throw ValidationException::withMessages([
                    'sales_end_at' => 'La fin de vente doit être après le début de vente.',
                ]);
            }

            $deadline = $offer->salesDeadline();

            if ($deadline && $offer->sales_start_at && $offer->sales_start_at->greaterThan($deadline)) {
                throw ValidationException::withMessages([
                    'sales_start_at' => 'Le début de vente ne peut pas dépasser la date et heure de l’activité.',
                ]);
            }

            if ($deadline && $offer->sales_end_at && $offer->sales_end_at->greaterThan($deadline)) {
                throw ValidationException::withMessages([
                    'sales_end_at' => 'La fin de vente ne peut pas dépasser la date et heure de l’activité.',
                ]);
            }
        });
    }

    public function offerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOfferableLabelAttribute(): string
    {
        return match ($this->offerable_type) {
            Event::class => 'Événement',
            Stand::class => 'Stand',
            Training::class => 'Formation',
            CallForProject::class => 'Appel à projets',
            CrowdfundingCampaign::class => 'Crowdfunding',
            default => 'Contenu',
        };
    }

    public function getOfferableTitleAttribute(): ?string
    {
        $record = $this->offerable;

        if ($record === null) {
            return null;
        }

        return match (true) {
            $record instanceof Event => $record->title,
            $record instanceof Stand => $record->name,
            $record instanceof Training => $record->title,
            $record instanceof CallForProject => $record->title,
            $record instanceof CrowdfundingCampaign => $record->title,
            default => null,
        };
    }

    private function salesDeadline(): ?Carbon
    {
        $record = $this->offerable;

        if ($record instanceof Event) {
            try {
                return $record->dates()->first()?->starts_at;
            } catch (\Throwable) {
                return null;
            }
        }

        if ($record instanceof Training) {
            return $record->starts_at;
        }

        if ($record instanceof CallForProject) {
            return $record->application_closes_at
                ?? $this->dateFromMeta($record->meta, 'event_at');
        }

        if ($record instanceof CrowdfundingCampaign) {
            return $record->ends_at
                ?? $this->dateFromMeta($record->meta, 'event_at');
        }

        if ($record instanceof Stand) {
            return $this->dateFromMeta($record->meta, 'event_at');
        }

        return null;
    }

    private function dateFromMeta(mixed $meta, string $key): ?Carbon
    {
        $value = is_array($meta) ? ($meta[$key] ?? null) : null;

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
