<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStatusHistory extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by_platform_user_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => TenantStatus::class,
            'to_status' => TenantStatus::class,
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function changedByPlatformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'changed_by_platform_user_id');
    }
}
