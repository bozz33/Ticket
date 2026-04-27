<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSupportTicket extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'platform_user_id',
        'reference',
        'subject',
        'requester_name',
        'requester_email',
        'status',
        'priority',
        'category',
        'opened_at',
        'last_activity_at',
        'resolved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
