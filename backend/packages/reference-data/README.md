# Reference Data Module

Owns reusable catalogs used by platform, tenants and the public frontend.

## Target responsibilities

- countries, states and cities
- currencies and payment method types
- languages and public statuses
- resource types and shared classification catalogs
- import and synchronization workflows

## Current ports

- `Ticket\ReferenceData\Contracts\CountryReferenceImport`
- `Ticket\ReferenceData\Contracts\CityReferenceSearch`

The legacy `App\Support\ReferenceData` classes are compatibility wrappers around the Laravel infrastructure adapters.
