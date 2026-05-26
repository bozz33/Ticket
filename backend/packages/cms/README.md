# CMS Module

Owns front pages, menus, sections and public brand configuration.

## Target responsibilities

- front pages and page sections
- header, footer and menu composition
- platform branding exposed to the public frontend
- CMS cache invalidation events

## Current ports

- `Ticket\Cms\Contracts\FrontCmsContent`

The legacy `App\Services\FrontCmsService` class is a compatibility wrapper around the Laravel infrastructure adapter.
