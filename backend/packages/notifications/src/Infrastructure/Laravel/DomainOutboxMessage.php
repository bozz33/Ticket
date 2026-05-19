<?php

namespace Ticket\Notifications\Infrastructure\Laravel;

use Illuminate\Database\Eloquent\Model;

class DomainOutboxMessage extends Model
{
    protected $connection = 'central';

    protected $table = 'domain_outbox_messages';

    protected $fillable = [
        'event_id',
        'type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'metadata',
        'status',
        'attempts',
        'available_at',
        'published_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
