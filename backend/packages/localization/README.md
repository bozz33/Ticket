# Localization Module

Owns languages, translation entries and fallback rules.

## Target responsibilities

- active languages and default locale
- key/value translation catalogs
- import/export workflows
- frontend translation payloads
- missing translation diagnostics

## Current ports

- `Ticket\Localization\Contracts\PublicLocalizationCatalog`

The public platform configuration endpoint consumes this package for frontend language and translation payloads.
