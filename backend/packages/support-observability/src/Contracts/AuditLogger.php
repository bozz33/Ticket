<?php

namespace Ticket\SupportObservability\Contracts;

use App\Models\PlatformAuditLog;
use Illuminate\Database\Eloquent\Model;

interface AuditLogger
{
    public function log(string $event, Model $subject, array $context = []): PlatformAuditLog;
}
