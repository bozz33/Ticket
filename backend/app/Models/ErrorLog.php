<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasPublicId;

    protected $connection = 'central';

    protected $fillable = [
        'public_id',
        'request_id',
        'level',
        'source',
        'type',
        'message',
        'exception_class',
        'file',
        'line',
        'status_code',
        'tenant_id',
        'user_id',
        'method',
        'url',
        'route',
        'ip',
        'user_agent',
        'context',
        'trace',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'line' => 'integer',
            'status_code' => 'integer',
            'tenant_id' => 'integer',
            'user_id' => 'integer',
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
