<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAuditLog extends Model
{
    use HasFactory;

    protected $connection = 'central';

    public $timestamps = false;

    protected $fillable = [
        'platform_user_id',
        'tenant_id',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'meta',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'meta' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function platformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
