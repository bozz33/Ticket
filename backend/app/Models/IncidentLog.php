<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentLog extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'payment_incident_id',
        'platform_support_ticket_id',
        'title',
        'severity',
        'status',
        'incident_type',
        'summary',
        'detected_at',
        'resolved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function paymentIncident(): BelongsTo
    {
        return $this->belongsTo(PaymentIncident::class);
    }

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(PlatformSupportTicket::class, 'platform_support_ticket_id');
    }
}
