<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'reference',
        'order_id',
        'status',
        'total_amount',
        'currency_code',
        'buyer_name',
        'buyer_email',
        'issued_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}
