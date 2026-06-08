<?php

namespace App\Models;

use App\Enums\CategoryScope;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CentralCategory extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'central';

    protected $table = 'categories';

    protected $fillable = [
        'public_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'module_scope',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'module_scope' => CategoryScope::class,
            'is_active' => 'boolean',
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

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $resolvedField = $field ?? 'slug';
        $query = $this->newQuery()->where($resolvedField, $value);

        // public_id is a UUID column; only compare it when the value is a valid UUID,
        // otherwise PostgreSQL raises a 22P02 invalid-text-representation error.
        if ($resolvedField !== 'public_id' && Str::isUuid((string) $value)) {
            $query->orWhere('public_id', $value);
        }

        return $query->first();
    }
}
