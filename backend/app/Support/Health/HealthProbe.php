<?php

namespace App\Support\Health;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Readiness probe for the API. Verifies the critical shared dependencies an instance needs
 * to serve traffic (the central database). Each check is isolated so one failing dependency
 * is reported without throwing, letting the caller return 503 while still describing why.
 */
class HealthProbe
{
    /**
     * @return array<string, array{ok: bool, error?: string}>
     */
    public function run(): array
    {
        return [
            'database' => $this->checkConnection((string) config('database.default', 'central')),
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function checkConnection(string $connection): array
    {
        try {
            DB::connection($connection)->select('select 1');

            return ['ok' => true];
        } catch (Throwable $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    public function isHealthy(array $checks): bool
    {
        foreach ($checks as $check) {
            if (! ($check['ok'] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
