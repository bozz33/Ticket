<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProfile extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'public_id',
        'slug',
        'display_name',
        'description',
        'email',
        'phone',
        'website_url',
        'logo_url',
        'banner_url',
        'is_verified',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
