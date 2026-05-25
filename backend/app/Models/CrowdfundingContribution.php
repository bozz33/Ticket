<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrowdfundingContribution extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'crowdfunding_campaign_id',
        'offer_id',
        'order_id',
        'buyer_user_id',
        'transaction_reference',
        'contributor_name',
        'contributor_email',
        'contributor_phone',
        'amount',
        'refunded_amount',
        'currency_code',
        'status',
        'is_anonymous',
        'paid_at',
        'refunded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'refunded_amount' => 'integer',
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CrowdfundingCampaign::class, 'crowdfunding_campaign_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
