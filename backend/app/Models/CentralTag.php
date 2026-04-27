<?php

namespace App\Models;

use App\Enums\CategoryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentralTag extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'tags';

    protected $fillable = [
        'public_id',
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

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? 'slug', $value)
            ->orWhere('public_id', $value)
            ->first();
    }
}
