<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontMenuItem extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'front_menu_id',
        'label',
        'href',
        'target',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(FrontMenu::class, 'front_menu_id');
    }
}
