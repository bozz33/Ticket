<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSocialLink extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'organization_profile_id',
        'platform',
        'label',
        'url',
        'is_public',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function organizationProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizationProfile::class);
    }
}
