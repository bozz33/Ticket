# Form Builder Module

Owns dynamic form definitions, fields, validations and submissions.

## Target responsibilities

- form definitions and versioning
- dynamic field catalog
- country, city and phone linked fields
- public submissions
- validation, attachments and audit trails

## Current ports

- `Ticket\FormBuilder\Contracts\FormSchemaValidator`
- `Ticket\FormBuilder\Contracts\FormSubmissionWriter`

The legacy `App\Services\Forms` classes are compatibility wrappers around the package application services.
