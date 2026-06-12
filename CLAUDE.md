# Ticket — Project Instructions

Multi-tenant ticketing and event platform. Laravel 12 backend + Next.js 14 frontend on
PostgreSQL.

## Architecture

- **Backend**: Laravel 12 (PHP ^8.2) in `backend/`. Multi-tenant via Stancl Tenancy (central + tenant
  connections, `central` / `tenant`).
- **Frontend**: Next.js 14 App Router in `front/`.
- **Auth**: custom Bearer token system (NOT Sanctum). `UserApiToken` model lives in the tenant
  DB, hashed with SHA-256. Service: `LaravelTenantTokenService` in
  `backend/packages/identity-access/`. Middleware alias `auth.tenant.api`.
- **Business logic** lives in `backend/packages/`. App services under `backend/app/Services/`
  are thin wrappers (<= 10 lines, extend the package base class). This is enforced by
  `tests/Unit/Architecture/ModularArchitectureBoundaryTest.php` — never put logic there.
- **Token abilities** are granular and role-based (buyers, scanners, owners/admins). Helpers
  used in subclass overrides must be `protected`, not `private`.

## Key paths

- Routes: `backend/routes/api/tenant.php`, `backend/routes/api/public.php`
- Bootstrap / middleware: `backend/bootstrap/app.php`
- Token config: `backend/config/ticket.php` (`token_expirations.*`)
- Frontend API client: `front/lib/data/account/client.ts`; auth helpers `front/lib/auth.ts`;
  middleware `front/proxy.ts`

## Tests

- Run: `cd backend && php artisan test --no-coverage`
- Tests run on **PostgreSQL** (`ticket_central_testing` + `ticket_tenant_testing`), configured
  in `backend/phpunit.xml` — not SQLite.
- Tenant schema helpers in tests must include all migration-added columns (`buyer_user_id`,
  `holder_user_id`, soft deletes on orders/receipts/access_passes/event_tickets).

## Conventions

- Code, comments, and docs in **English**. No emojis, no AI attribution traces anywhere.
  Professional, human-authored style.
- **Never commit or push without explicit user approval.** Implement, then test, then commit
  only when authorized. On the default branch, branch first.
- After a batch of changes, before requesting a commit: `/code-review` → `/simplify` →
  `/security-review` → `/verify`. Use `/code-review` at high effort for auth, payments, and
  check-in changes. `/security-review` is mandatory before any push to a shared branch.
- The user prefers French in chat. Code and identifiers stay in their conventional language.

## Tenant DB notes

- Tenant DB naming: `ticket_` + slug with `-` replaced by `_` (stored in
  `tenants.database_name`).
- `php artisan tenants:run migrate` may report "Nothing to migrate" even with pending files;
  if so, apply migrations via a tinker loop over each tenant DB.
