<?php

namespace App\Models;

use App\Enums\CategoryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'tags';

    protected $fillable = [
        'public_id',
        'synced_from_tag_id',
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
}
