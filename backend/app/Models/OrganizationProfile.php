<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationProfile extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'legal_name',
        'display_name',
        'description',
        'email',
        'phone',
        'website_url',
        'logo_url',
        'banner_url',
        'primary_color',
        'secondary_color',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country_code',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OrganizationContact::class)->orderBy('sort_order')->orderBy('id');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(OrganizationSocialLink::class)->orderBy('sort_order')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class)->latest();
    }
}
