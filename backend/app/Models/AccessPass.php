<?php

namespace App\Models;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPass extends Model
{
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'access_code',
        'order_id',
        'offer_id',
        'type',
        'status',
        'holder_name',
        'holder_email',
        'used_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccessPassType::class,
            'status' => AccessPassStatus::class,
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(AccessPassScan::class)->orderByDesc('scanned_at');
    }

    public function isConsumable(): bool
    {
        return $this->status->isConsumable()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function toQrPayload(): array
    {
        return [
            'code' => $this->access_code,
            'type' => $this->type->value,
            'public_id' => $this->public_id,
        ];
    }
}
