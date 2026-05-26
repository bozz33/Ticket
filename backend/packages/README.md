# Internal Packages

Ce dossier contient les modules internes du backend. Chaque module doit etre traite comme un package reutilisable, meme s'il est charge dans le monolithe Laravel.

## Standards

- un namespace propre, par exemple `Ticket\Payments`
- un service provider par module
- des contrats publics dans `src/Contracts`
- les details Laravel/Eloquent dans `src/Infrastructure/Laravel`
- aucun controleur ou panel Filament ne doit dependre directement d'une implementation interne du module
- les bindings metier du module doivent vivre dans son service provider, pas dans `AppServiceProvider`
- les tests d'architecture doivent proteger les frontieres
- les migrations propres aux modules doivent vivre sous `packages/{module}/database/migrations`
- les tests propres aux modules doivent vivre sous `packages/{module}/tests`

## Modules actifs

- `payments` : actif
- `tenancy` : actif
- `ticketing` : actif
- `public-catalog` : actif
- `notifications` : actif

## Modules extraits ou operationalises

Ces packages posent maintenant des frontieres testees. Certains portent deja le code applicatif extrait; les autres exposent des contrats stables pour absorber progressivement le code historique et les migrations.

- `identity-access` : auth platform, auth tenant, tokens, RBAC, verification email
- `reference-data` : pays, villes, devises, langues, statuts publics, types de ressources
- `media-documents` : uploads, documents, images, QR assets, PDF, quotas
- `cms` : pages front, menus front, sections, branding, header/footer
- `seo` : SEO, meta, open graph, robots, sitemap
- `localization` : langues actives, traductions, fallbacks
- `engagement` : likes, follows, compteurs, tendances
- `access-control` : scan QR, check-in, anti double-scan, journaux de scan
- `finance-accounting` : settlements, payouts, exports, reconciliation, incidents finance
- `support-observability` : support, audit logs, incident logs, KPI snapshots
- `form-builder` : definitions de formulaires, champs dynamiques, validations, submissions
- `content-events` : evenements, dates, lieux, publication
- `content-training` : formations, sessions, inscriptions, attestations
- `content-stands` : stands, salons, zones, reservations exposants
- `content-calls-for-projects` : appels, candidatures, evaluation
- `content-crowdfunding` : campagnes, contributions, progression publique
