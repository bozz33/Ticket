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

## Modules cibles

- `payments` : actif
- `tenancy` : actif
- `ticketing` : actif
- `public-catalog` : actif
- `notifications` : actif
- `training` : a extraire
- `crowdfunding` : a extraire
- `calls-for-projects` : a extraire
- `stands` : a extraire
