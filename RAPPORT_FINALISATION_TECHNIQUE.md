# Rapport de finalisation technique — Projet Ticket

Branche : `codex-fix-tenant-profile-storage`
Date : 2026-06-07
Périmètre : audit complet, corrections de bugs, migration de l'infra de test vers PostgreSQL, module d'observabilité, recréation des données de démo, ressources panel Formations/Stands, correction du login acheteur et des builds.

---

## 1. Synthèse

Le projet a été audité en profondeur (backend Laravel 11 multi-tenant + frontend Next.js 14) puis corrigé et stabilisé. Plusieurs bugs bloquants pour la production ont été identifiés et corrigés, l'infrastructure de test a été alignée sur la base de production (PostgreSQL), un module d'observabilité interne a été ajouté, et les modules de contenu Formations/Stands ont été rétablis dans le panel organisateur.

État final vérifié :
- **166 tests** backend (PostgreSQL) au vert, **648+ assertions**
- **Pint** propre sur tout l'arbre
- **TypeScript** et **lint** front au vert, **build de production** fonctionnel
- Crawl runtime sans erreur (front + API)

---

## 2. Bugs corrigés

### 2.1 Route binding UUID — crash production sur PostgreSQL (critique)
`resolveRouteBinding()` ajoutait systématiquement `orWhere('public_id', $value)` sur une colonne `uuid`. Une valeur non-UUID (slug/code) déclenchait `SQLSTATE 22P02` → HTTP 500.
- Impacts : **webhook de paiement** (`PaymentGateway`), `CentralCategory`, `CentralTag` (routes admin), `Tenant` (latent).
- Fix : garde `Str::isUuid()` avant toute comparaison sur `public_id`.
- Invisible aux tests car ceux-ci tournaient sur SQLite (typage souple).

### 2.2 Formulaires publics cassés sur PostgreSQL (critique)
`PublicFormSubmissionController::resolveFormDefinition` comparait un UUID à la colonne `id` (`bigint`) → `22P02`. Affichage/soumission de formulaires publics cassés.
- Fix : garde `ctype_digit()` avant comparaison sur `id`.
- Gardes défensifs ajoutés dans `TicketingCheckoutItemResolver` et `EventTicketInventoryService`.

### 2.3 Webhook de paiement — vérification de signature en *fail-open* (sécurité)
`verifySignature` retournait silencieusement pour toute passerelle non-Paystack.
- Fix : *fail-closed* (rejet explicite des passerelles non supportées) ; logique Paystack isolée dans `verifyPaystackSignature`.

### 2.4 Check-in — race condition (TOCTOU)
`reset`/`revoke`/`reactivate` vérifiaient le statut sur un modèle périmé et écrivaient sans verrou.
- Fix : verrou `lockForUpdate` dans la transaction via un helper `lockPass()`, cohérent avec `consume()`.

### 2.5 Détail d'événement — `<img src="">` (erreur React + rechargement complet)
L'image organisateur valait `""` et le code utilisait `?? coverImageUrl` (qui ne rattrape pas la chaîne vide).
- Fix : helper `resolveImageSrc()` (première valeur non-vide, sinon `undefined`) appliqué au détail, sticky, intervenants, galerie et flux checkout.

### 2.6 Connexion au panel acheteur impossible
`front/.env.local` contenait `NEXT_PUBLIC_TENANT_SLUG=demo`, alors que le slug réel est `demo-front-buyer`. Le login appelait `/tenants/demo/auth/login` → tenant introuvable → 401 « Tenant context is required ».
- Fix : valeur corrigée. Flux login → cookies → `/compte` vérifié (200).

### 2.7 Build de production front cassé (×2)
- Script `build` sans `--webpack` → conflit Turbopack/webpack (Next 16). Aligné sur `dev`/`build:ci`.
- `PassQrPanel.tsx` utilisait `dynamic(ssr:false)` hors Client Component → ajout de `"use client"`.

---

## 3. Migration de l'infrastructure de test vers PostgreSQL

Les tests forçaient SQLite `:memory:`, ce qui masquait toute une classe de bugs liés au typage strict de PostgreSQL.
- `phpunit.xml` repointé sur PostgreSQL (`ticket_central_testing`, `ticket_tenant_testing`).
- ~13 fichiers de test convertis (drop+create idempotent, `dropAllTables()` cascade).
- Lacunes de schéma de test comblées (`event_dates`, `buyer_user_id`, `holder_user_id`).
- SQLite retiré de `composer.json` (post-create).
- Résultat : suite complète au vert sur PostgreSQL, fidèle à la production.

---

## 4. Module d'observabilité interne

Capture exhaustive sans dépendance externe (pas de Sentry/Telescope/Pulse).
- Table centrale interrogeable `error_logs` (+ modèle `ErrorLog`).
- `ErrorLogWriter` résilient (ne casse jamais la requête observée).
- Exceptions backend persistées via `$exceptions->report()` + contexte enrichi (`request_id`, `tenant_id`, `user_id`).
- Corrélation `X-Request-Id` (middleware `AssignRequestId`).
- Erreurs front capturées : `app/error.tsx` + `app/global-error.tsx` → endpoint Next → backend `POST /api/v1/observability/client-events`.
- Couverture : `ObservabilityModuleTest`.

---

## 5. Données de démo et appels à projets

- `TenantDemoEventsSeeder` : suppression + recréation sur les 3 tenants actifs, toutes catégories, variantes payantes ET gratuites.
- Correctifs : stands toujours payants, `Offer.sales_end_at` dépassant la date d'activité.
- **Appels à projets** : chaque appel démo dispose d'un `FormDefinition` publié (formbuilder du panel) ; le formulaire de candidature provient bien du panel.
- La projection catalogue centrale est reconstruite automatiquement après seed (`ticket:rebuild-public-catalog`).

---

## 6. Modules Formations & Stands dans le panel organisateur (Architecture 2)

Constat : Formations et Stands sont des modules publics à part entière (`Training`/`Stand`), mais leurs ressources Filament avaient été supprimées → impossible de les gérer côté organisateur (seul le seeder les créait). Les « catégories » training/stand n'existaient pas.

Décision validée : **un modèle + un module public + une ressource Filament par type de contenu** (les catégories restent une taxonomie).

Livré :
- Ressources Filament **Formations** et **Stands** (Liste/Créer/Éditer + Relation Manager d'offres), calquées sur Crowdfunding/Events, groupe « Création & modules ».
- Catégories `training` (6) et `stand` (5) ajoutées en central et synchronisées vers les tenants.
- Données démo Event/Training/Stand dotées de catégories du bon scope (filtres catalogue alimentés).
- Catégorie redondante « Formation » de scope `event` retirée proprement (migration réversible ; events référents nullés via `nullOnDelete`).

---

## 7. Maintenance effectuée

`cache:clear`, `config:clear`, `route:clear`, `view:clear`, `event:clear`, `optimize:clear`.
(`config:cache`/`route:cache` volontairement non activés en dev pour ne pas figer la configuration.)

---

## 8. Vérifications finales

| Contrôle | Résultat |
|---|---|
| Tests backend (PostgreSQL) | 166 passés |
| Pint | propre |
| TypeScript front | OK |
| Lint / sécurité front | OK |
| Build production front | OK |
| Routes panel Formations/Stands | enregistrées |
| Pages publiques (toutes catégories) | 200 |
| Login acheteur → panel | OK |

---

## 9. Points laissés à la décision

- `migrate:fresh --seed` : non exécuté (destructif).
- Réduction de duplication entre ressources de contenu (trait/builder partagé) : amélioration ultérieure possible.
- Tests manuels end-to-end recommandés avant production : paiement Paystack réel, scan QR sur terminal, parcours mobile.
