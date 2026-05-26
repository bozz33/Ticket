# Access Control Module

Owns QR validation, check-in and scan journals.

## Target responsibilities

- pass lookup and validation
- check-in consume, revoke, reset and reactivate commands
- anti double-scan rules
- scanner agent and entry point boundaries
- offline sync integration points later

## Current ports

- `Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow`

`ticketing` keeps a compatibility contract for existing panels and API controllers, but delegates check-in behavior to this package.
