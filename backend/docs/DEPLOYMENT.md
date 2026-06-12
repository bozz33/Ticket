# Deployment runbook

Operational guide for deploying the Ticket backend (Laravel 12, PostgreSQL, multi-tenant
via Stancl Tenancy). The Next.js frontend (`front/`) deploys separately.

## Requirements

- PHP 8.2+ with extensions: `mbstring`, `intl`, `pdo_pgsql`, `bcmath`, `gd`
- PostgreSQL 14+ (one cluster hosting the central database and one database per tenant)
- A queue worker runtime (for notifications and async fulfilment retries)
- Outbound HTTPS to the payment gateway (Paystack)

## Environment

Copy `.env.example` to `.env` and set at least:

- `APP_KEY` — generate with `php artisan key:generate`
- `APP_ENV=production`, `APP_DEBUG=false`
- Central DB: `DB_CONNECTION=central`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `TENANT_API_TOKEN_TTL_MINUTES` (default 30 days) — see `config/ticket.php`
- Paystack credentials and the webhook secret used by `PaymentWebhookService`

Never commit real credentials. The central database is the default connection
(`config('database.default') === 'central'`).

## Release steps

1. `composer install --no-dev --optimize-autoloader`
2. `php artisan config:cache route:cache event:cache`
3. Run central migrations: `php artisan migrate --force`
4. Run tenant migrations for every tenant (see caveat below)
5. Rebuild the public catalog projection: `php artisan ticket:rebuild-public-catalog`
6. Restart the queue workers and PHP-FPM/Octane

### Tenant migrations (caveat)

`php artisan tenants:run migrate` can report "Nothing to migrate" even when tenant-path
migrations are pending. When that happens, apply per tenant and record the migration in the
tenant's own `migrations` table. Example (one additive migration):

```php
// php artisan tinker
$mig = '2026_06_12_000100_add_receipt_number_sequencing';
foreach (\App\Models\Tenant::all() as $t) {
    $t->run(function () use ($mig) {
        $schema = \Illuminate\Support\Facades\Schema::connection('tenant');
        // apply the migration's up() DDL guarded by hasTable/hasColumn (idempotent), then:
        $db = \Illuminate\Support\Facades\DB::connection('tenant');
        if ($schema->hasTable('migrations') && ! $db->table('migrations')->where('migration', $mig)->exists()) {
            $batch = (int) $db->table('migrations')->max('batch') + 1;
            $db->table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
        }
    });
}
```

Always make tenant migrations additive (new tables / nullable columns) so they apply without
locking large tables and remain safe to retry.

## Health checks

- **Liveness** — `GET /up` (Laravel default). The process is up. Use for restart decisions.
- **Readiness** — `GET /api/v1/health`. Verifies the central database. Returns `200` with
  `{"status":"ok","checks":{...}}` when ready, `503` with `{"status":"degraded"}` when a
  dependency is down. Point the load balancer / orchestrator readiness probe here so traffic
  is only routed to instances that can reach the database.

## Observability

Errors and request telemetry are captured internally to the central `error_logs` table with a
correlation request id (see `app/Support/Observability/ErrorLogWriter.php` and the
`AssignRequestId` middleware). There is no external APM; query `error_logs` and
`incident_logs` for incidents. Payment webhook failures also open `payment_incidents`.

## Payment integrity (do not bypass)

- Webhook signatures fail closed (unknown gateway is rejected).
- Paid amounts are verified against the server-quoted total before fulfilment
  (`PaidAmountGuard`) on both the webhook and the client-polled verify paths.
- Stock is locked (`lockForUpdate`) during fulfilment to prevent overselling.

## Rollback

1. Redeploy the previous release artifact.
2. Run `php artisan migrate:rollback --force` only if the release added central migrations
   that are safe to reverse. Tenant migrations are additive; prefer leaving the new
   tables/columns in place (unused) over a risky multi-tenant rollback.
3. `php artisan config:cache route:cache` and restart workers.

## Smoke test after deploy

- `GET /up` → 200
- `GET /api/v1/health` → 200, `status: ok`
- `GET /api/v1/mobile/bootstrap` → 200 with the expected capabilities
- One end-to-end buyer checkout in a staging tenant, then scan the issued pass
