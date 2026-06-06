<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserApiToken extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function can(string $ability): bool
    {
        if ($this->abilities === null) {
            return true;
        }

        if (in_array('*', $this->abilities, true) || in_array($ability, $this->abilities, true)) {
            return true;
        }

        // Support prefix wildcards: "notifications.*" covers "notifications.read", "notifications.write", etc.
        $prefix = strstr($ability, '.', true);
        if ($prefix !== false && in_array($prefix.'.*', $this->abilities, true)) {
            return true;
        }

        return false;
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
