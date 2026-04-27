<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
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
}
