<?php

namespace App\Models;

use App\Enums\FrontMenuLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrontMenu extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'key',
        'title',
        'location',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'location' => FrontMenuLocation::class,
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FrontMenuItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
