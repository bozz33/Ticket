<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use HasPublicId;
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'buyer_user_id',
        'reference',
        'receipt_number',
        'order_id',
        'status',
        'total_amount',
        'refunded_amount',
        'currency_code',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'issued_at',
        'refunded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'refunded_amount' => 'integer',
            'issued_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}
