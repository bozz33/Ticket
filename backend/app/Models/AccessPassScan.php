<?php

namespace App\Models;

use App\Enums\ScanResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessPassScan extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'access_pass_id',
        'scanned_by',
        'action',
        'result',
        'terminal_id',
        'ip_address',
        'meta',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => ScanResult::class,
            'meta' => 'array',
            'scanned_at' => 'datetime',
        ];
    }

    public function accessPass(): BelongsTo
    {
        return $this->belongsTo(AccessPass::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function isGranted(): bool
    {
        return $this->result === ScanResult::Granted;
    }
}
