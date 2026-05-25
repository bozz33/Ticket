<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'code',
        'name',
        'description',
        'module',
        'default_enabled',
        'is_public',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function tenantOverrides(): HasMany
    {
        return $this->hasMany(TenantFeatureFlag::class);
    }
}
