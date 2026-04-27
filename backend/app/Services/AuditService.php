<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(string $event, Model $subject, array $context = []): PlatformAuditLog
    {
        $actor = Auth::guard('platform')->user();
        $tenant = $context['tenant'] ?? ($subject instanceof Tenant ? $subject : null);

        return PlatformAuditLog::query()->create([
            'platform_user_id' => $actor?->getKey(),
            'tenant_id' => $tenant?->getKey(),
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'subject_label' => method_exists($subject, 'getAttribute') ? ($subject->getAttribute('name') ?? $subject->getAttribute('code') ?? $subject->getAttribute('key')) : null,
            'changes' => $this->sanitize($context['changes'] ?? []),
            'meta' => $this->sanitize($context['meta'] ?? []),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'logged_at' => now(),
        ]);
    }

    protected function sanitize(array $payload): array
    {
        $sensitiveKeys = [
            'password',
            'remember_token',
            'secret_key',
            'webhook_secret',
            'database_password',
        ];

        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $sensitiveKeys, true)) {
                $payload[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitize($value);
            }
        }

        return $payload;
    }
}
