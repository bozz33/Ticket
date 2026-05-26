# Engagement Module

Owns likes, follows, counters and trend projections.

## Target responsibilities

- content likes and organization follows
- weekly and total engagement counters
- popular content ranking
- follower audience projections
- idempotent engagement commands

## Current ports

- `Ticket\Engagement\Contracts\EventEngagementWorkflow`
- `Ticket\Engagement\Contracts\OrganizationAudienceWorkflow`

`ticketing` keeps compatibility contracts for existing API controllers and panels, but delegates likes and follows to this package.
