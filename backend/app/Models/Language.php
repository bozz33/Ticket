<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'code',
        'locale',
        'name',
        'native_name',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TranslationEntry::class);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? 'code', strtolower($value))
            ->orWhere('locale', strtolower($value))
            ->first();
    }
}
