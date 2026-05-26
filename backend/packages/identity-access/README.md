# Identity Access Module

Owns authentication, API tokens, roles, permissions and account verification.

## Target responsibilities

- platform and tenant authentication
- RBAC contracts and permission catalog
- API tokens, refresh flows and session security
- email verification and password reset workflows
- MFA and SSO integration points later

This package is scaffolded first because almost every module depends on identity boundaries.

## Current ports

- `Ticket\IdentityAccess\Contracts\PlatformTokenIssuer`
- `Ticket\IdentityAccess\Contracts\TenantTokenIssuer`

The legacy `App\Services\Auth` classes are compatibility wrappers around the Laravel infrastructure adapters.
