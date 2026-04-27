<?php

namespace App\Models;

use App\Enums\CategoryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'categories';

    protected $fillable = [
        'public_id',
        'synced_from_category_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'module_scope',
        'sort_order',
        'is_active',
        'sync_checksum',
        'last_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'module_scope' => CategoryScope::class,
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
