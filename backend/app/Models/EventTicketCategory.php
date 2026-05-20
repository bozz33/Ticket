<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicketCategory extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class, 'ticket_category_id');
    }
}
