# Security Hardening — Pre-Production Audit

**Branch:** `codex-fix-tenant-profile-storage`
**Scope:** Backend Laravel API + Next.js frontend
**Test result:** 143/143 backend tests passing, TypeScript 0 errors

---

## Overview

This document records the security and reliability fixes applied to the Ticket platform
before production deployment. Eight issue categories were addressed, covering authentication,
authorization, concurrency, payment integrity, and repository hygiene.

---

## Lot 1 — Token Abilities: Principle of Least Privilege

**File:** `backend/app/Http/Controllers/Api/V1/Auth/TenantAuthController.php`

### Problem

Every token issued at login and registration received `['*']` (wildcard), granting full
API access regardless of the user's role. A buyer's token could theoretically call
administrative check-in management endpoints.

### Fix

- Added `BUYER_ABILITIES` constant (11 scoped permissions: `profile.read`, `profile.write`,
  `orders.read`, `receipts.read`, `passes.read`, `notifications.*`, `engage.write`,
  `settings.*`, `refunds.write`).
- Added `SCANNER_ABILITIES` constant (`passes.read`, `passes.scan`).
- Added `resolveAbilitiesForUser(User $user): array` which maps user roles to ability sets:
  - Owners, admins, organizers → `['*']`
  - Scanners → `SCANNER_ABILITIES`
  - All others (buyers) → `BUYER_ABILITIES`
- `register()` always issues `BUYER_ABILITIES` (new users are buyers by definition).
- Wildcard `['*']` removed from both `login()` and `register()`.

Token expiration was already implemented in `LaravelTenantTokenService` via
`config('ticket.token_expirations.tenant_api_minutes')`, defaulting to 30 days.
Password hashing was already correct (`Hash::make` + `'password' => 'hashed'` cast).

---

## Lot 2 — Middleware: Account Active State Verification

**File:** `backend/app/Http/Middleware/AuthenticateTenantApi.php`

### Problem

After token validation, the middleware did not verify whether the associated user account
was still active. A user disabled after login could continue using their token until it
expired naturally.

### Fix

After `findToken()`, the user is extracted from the token relation and checked:

```php
$user = $apiToken->user;

if ($user === null || ! $user->is_active) {
    return $this->unauthorized('Compte désactivé ou introuvable.');
}
```

The request attribute `tenant_user` is now set to the already-resolved `$user` variable
rather than re-accessing `$apiToken->user`, avoiding a second property access.

---

## Lot 3 — Route Authorization: Check-in Ability Enforcement

**Files:**
- `backend/app/Http/Middleware/CheckTokenAbility.php` (new)
- `backend/bootstrap/app.php`
- `backend/routes/api/tenant.php`

### Problem

Check-in management routes (`/checkin/reset`, `/checkin/revoke`, `/checkin/reactivate`)
were protected only by authentication (`auth.tenant.api`). Any valid token, including a
buyer's token, could call these sensitive state-change endpoints.

### Fix

Created `CheckTokenAbility` middleware, registered under the `ability` alias. It reads
the `tenant_api_token` attribute set by `AuthenticateTenantApi` and verifies that the
token's ability set satisfies all required abilities:

```php
public function handle(Request $request, Closure $next, string ...$abilities): Response
{
    $token = $request->attributes->get('tenant_api_token');
    // ...
    foreach ($abilities as $ability) {
        if (! $token->can($ability)) {
            return new JsonResponse(['message' => 'Action non autorisée pour ce token.'], 403);
        }
    }
    return $next($request);
}
```

Route enforcement applied:

| Endpoint | Required ability |
|----------|-----------------|
| `GET /checkin/preview` | `passes.scan` |
| `POST /checkin/consume` | `passes.scan` |
| `POST /checkin/reset` | `passes.manage` |
| `POST /checkin/revoke` | `passes.manage` |
| `POST /checkin/reactivate` | `passes.manage` |

Buyer tokens (`BUYER_ABILITIES`) lack both `passes.scan` and `passes.manage`.
Owner/admin tokens (`['*']`) satisfy all abilities. Scanner tokens include `passes.scan`
but not `passes.manage`.

---

## Lot 4 — Concurrency: Pessimistic Lock on Pass Consumption

**File:** `backend/packages/access-control/src/Application/AccessPassCheckinService.php`

### Problem

`consume()` checked `isConsumable()` before opening a database transaction. Under concurrent
requests (two scanners scanning the same QR code simultaneously), both requests could read
the pass as active before either transaction committed, resulting in double validation.

### Fix

The pre-transaction `isConsumable()` check was removed. The pass is now re-fetched inside
the transaction using `SELECT FOR UPDATE`:

```php
$locked = AccessPass::on($connectionName)
    ->whereKey($pass->getKey())
    ->lockForUpdate()
    ->firstOrFail();

if (! $locked->isConsumable()) { ... }
```

The second concurrent request will block on `lockForUpdate()` until the first transaction
commits. At that point it reads `status = Used` and returns `AlreadyUsed` — correct behavior.

Helper methods `resolveReadResult`, `recordScan`, and `buildResponse` were changed from
`private` to `protected` to allow the override pattern used in the app service layer.

---

## Lot 5 — Stock Control: Overselling Prevention

**File:** `backend/packages/payments/src/Application/OrderFulfillmentService.php`

### Problem

`ensureAccessPasses()` created access passes and incremented `quantity_sold` without first
verifying that available stock was sufficient. Under concurrent payment webhooks for the
same offer, multiple fulfillments could exceed `quantity_total`.

### Fix

Inside `ensureAccessPasses()`, before creating any pass, the offer row is locked with
`SELECT FOR UPDATE` and stock availability is verified:

```php
$offer = Offer::on($connectionName)->whereKey($offer->getKey())->lockForUpdate()->first();

if ($maxQty !== null && $maxQty > 0) {
    $needed = max(0, $order->quantity - $existingCount);
    $alreadySold = (int) ($offer->quantity_sold ?? 0);

    if ($needed > 0 && ($alreadySold + $needed) > $maxQty) {
        throw new \RuntimeException(
            'Stock insuffisant pour l\'offre #%d : %d unité(s) demandée(s), %d disponible(s).'
        );
    }
}
```

The exception propagates through `fulfill()` → `fulfillSuccessfulTransaction()` → `receive()`,
which records a `PaymentIncident` with the failure reason. The webhook returns HTTP 400 to
the gateway so it can retry or flag the event.

Offers with `quantity_total = null` or `0` are treated as unlimited (crowdfunding-style),
preserving existing behavior.

`ensureAccessPasses()` was changed from `private` to `protected` to enable future override
in the app service layer if needed.

---

## Lot 6 — Payment Webhook: Metadata Injection Prevention

**File:** `backend/packages/payments/src/Application/PaymentWebhookService.php`

### Problem

`buildFulfillmentPayload()` merged checkout data and webhook metadata using:

```php
$metadata = array_replace($checkout, $webhookMetadata);
```

This allowed webhook-supplied metadata to override server-stored values such as `offer_id`,
`quantity`, `buyer_email`, and `buyer_user_id`. Although the Paystack signature protects the
payload from external tampering, a compromised or malicious webhook source could still
substitute these fields.

### Fix

The merge order is reversed so that server-side checkout data (stored in
`PlatformTransaction->meta->checkout` at payment initialization) takes precedence:

```php
// Server-side checkout data takes precedence over webhook-supplied metadata.
$metadata = array_replace((array) Arr::get($payload, 'data.metadata', []), $checkout);
```

This means the fields stored by the server at checkout initialization are authoritative
and cannot be overridden by the webhook payload, regardless of signature validity.

---

## Lot 7 — Frontend: Typed API Errors and Session Cleanup on 401

**Files:**
- `front/lib/data/account/client.ts`
- `front/components/account/layout/useAccountPanelSession.ts`

### Problem 1: Silent error swallowing

`apiFetch<T>()` returned `null` for all error conditions (401, 404, 500, network failure),
making it impossible for callers to distinguish between "not found", "unauthorized", and
"server error".

### Fix

Added `ApiResult<T>` discriminated union type and `apiFetchResult<T>()` function:

```typescript
export type ApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; status: number; message: string };
```

`apiFetch<T>()` is preserved unchanged for backward compatibility but internally delegates
to `apiFetchResult<T>()`. New code should use `apiFetchResult<T>()` to handle error codes.

### Problem 2: Stale cookies after session expiry

When `useAccountPanelSession` detected a 401 from `/api/account/me`, it redirected to
the login page without clearing the server-side authentication cookies. The middleware
would then re-serve the protected account area on the next navigation because the cookie
still existed.

### Fix

Before redirecting on 401, the hook now calls `/api/account/logout` which invokes
`clearAuthCookies()` server-side:

```typescript
if (response.status === 401) {
    await fetch("/api/account/logout", { method: "POST" }).catch(() => {});
    router.push(`/compte/connexion?redirect=...`);
}
```

---

## Lot 8 — Repository Hygiene: Remove Generated Files from Git

### Problem

737 browser profile and screenshot files were tracked by git:
- `front/.edge-desktop/` — Microsoft Edge browser profile (Playwright)
- `front/.edge-mobile/` — Microsoft Edge mobile profile (Playwright)
- `front/.chrome-shot/` — Chromium screenshot capture artifacts
- `front/.screenshots/` — Test screenshot output

These files are binary, large, change on every test run, and have no place in version
control. They were already listed in `.gitignore` but had been committed before the ignore
rules were in place.

### Fix

```bash
git rm -r --cached front/.edge-desktop/ front/.edge-mobile/ front/.chrome-shot/ front/.screenshots/
```

Files removed from tracking only (`--cached`). Working directory is unaffected.
`.gitignore` entries already cover these paths and will prevent future accidental additions.

---

## Test Coverage

Three new unit test files added:

| File | Tests | Assertions |
|------|-------|-----------|
| `TenantTokenAbilitiesTest.php` | 5 | Buyer abilities, scanner abilities, wildcard, null, constant presence |
| `CheckTokenAbilityMiddlewareTest.php` | 5 | Missing token (401), wrong ability (403), correct ability (200), wildcard, multi-ability |
| `AuthenticateTenantApiMiddlewareTest.php` | 5 | No bearer, invalid token, inactive user, null user, active user passthrough |

Full suite result: **143 passed, 0 failed** (620 assertions).

---

## Remaining Work (Post-Stabilization)

The following items were identified but deferred:

- **Checkout session / payment intent table**: Store a server-side record before Paystack
  redirect to enable amount verification at webhook time. The metadata merge fix (Lot 6)
  provides partial protection without this.
- **Order status separation**: Split `payment_status`, `fulfillment_status`, and
  `order_status` into separate fields for cleaner state tracking.
- **Receipt numbering**: Move from `RCP-yymmdd-random` to sequential `RCP-YYYY-NNNNNN`
  per tenant for legal compliance.
- **CSS split**: Decompose `globals.css` (3800+ lines) into scoped module files.
- **Public pass data masking**: Limit data returned from the public pass verification
  endpoint (mask email, phone).
- **Webhook fulfillment retry queue**: Dispatch failed fulfillments to a queue job with
  exponential backoff instead of relying on gateway retry behavior.
