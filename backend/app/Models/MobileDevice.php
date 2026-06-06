<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileDevice extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'user_id',
        'device_id',
        'platform',
        'push_provider',
        'push_token',
        'app_version',
        'device_name',
        'locale',
        'timezone',
        'last_seen_at',
        'revoked_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
