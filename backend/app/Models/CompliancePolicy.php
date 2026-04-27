<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompliancePolicy extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'description',
        'policy_type',
        'status',
        'effective_from',
        'effective_to',
        'requirements',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'requirements' => 'array',
            'meta' => 'array',
        ];
    }
}
