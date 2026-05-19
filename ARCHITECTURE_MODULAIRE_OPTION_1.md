# Architecture Modulaire - Option 1

## Objectif

L'objectif est de garder la vitesse de Laravel + Filament + Next.js tout en donnant au backend une structure de grand projet : modules reutilisables, contrats stables, dependances explicites et extraction possible vers des micro-services plus tard.

Le projet reste un monolithe modulaire pour l'instant. Les modules sont charges dans la meme application, mais le code applicatif ne doit plus dependre directement des implementations internes.

## Decision

Le backend adopte une architecture par packages internes :

```text
backend/
  app/
    Http/
    Filament/
    Services/
  packages/
    payments/
    tenancy/
    ticketing/
    public-catalog/
    notifications/
```

Les modules actifs sont :

- `payments` : paiement, pricing, remboursement, reversement et webhooks
- `tenancy` : provisioning tenant, lifecycle, stockage, settings et referentiels
- `ticketing` : evenements, commandes, recus, pass, check-in, likes et audience
- `public-catalog` : portail public, pages front CMS et candidatures publiques
- `notifications` : abstraction d'envoi pour isoler les canaux applicatifs

## Regles d'architecture

1. Les controleurs, panels Filament et workflows applicatifs consomment des contrats de module.
2. Les implementations Laravel/Eloquent restent derriere des adaptateurs.
3. Les modules ne s'appellent pas entre eux via des classes concretes.
4. Les integrations externes passent par des ports clairs : gateways, webhooks, jobs, events.
5. Les migrations et tests d'un module vivent dans le package du module des que le module est extrait.
6. Un module peut etre deplace vers un micro-service seulement quand ses contrats sont stables.
7. `AppServiceProvider` reste reserve aux services transversaux ; chaque module enregistre ses propres bindings.

## Structure standard d'un module

```text
packages/{module}/
  composer.json
  README.md
  src/
    Contracts/
    Domain/
    Application/
    Infrastructure/
      Laravel/
    {Module}ServiceProvider.php
  database/
    migrations/
  routes/
  tests/
```

## Module Payments

Le module `payments` expose maintenant des contrats :

- `CheckoutManager`
- `PaymentWebhookReceiver`
- `PricingEngine`
- `RefundManager`
- `PayoutManager`
- `SettlementWorkflow`
- `TenantRefundManager`

Les adaptateurs `Infrastructure/Laravel` deleguent aux services applicatifs du package dans `src/Application`.

Les anciennes classes `App\Services\Payments` restent uniquement comme wrappers de compatibilite. Le provider du module enregistre aussi les services de support paiement necessaires aux adapters, par exemple la resolution des credentials gateway et le traitement des webhooks.

## Module Tenancy

Contrats exposes :

- `TenantProvisioner`
- `TenantLifecycleManager`
- `TenantDestroyer`
- `TenantStorage`
- `TenantReferenceCatalog`
- `TenantProfileManager`
- `TenantSettingsManager`

Les services applicatifs du module vivent dans `packages/tenancy/src/Application`. Les anciennes classes `App\Services\Tenancy` correspondantes sont des wrappers de compatibilite.

## Module Ticketing

Contrats exposes :

- `EventCatalog`
- `DocumentCatalog`
- `OrderCatalog`
- `ReceiptCatalog`
- `AccessPassCatalog`
- `AccessPassCheckin`
- `EventEngagement`
- `OrganizationAudience`
- `BuyerRefundRequests`

Les services applicatifs du module vivent dans `packages/ticketing/src/Application`. Les anciens services `App\Services\Tenancy` lies aux commandes, documents, evenements, pass et remboursements acheteur sont des wrappers de compatibilite.

## Module Public Catalog

Contrats exposes :

- `PublicContentCatalog`
- `FrontContent`
- `CallForProjectFormBuilder`
- `CallForProjectApplications`

Le module contient aussi un read model central `public_catalog_items` pour les listes publiques globales multi-tenants. La commande `ticket:rebuild-public-catalog` reconstruit cette projection et le service conserve un fallback compatible tant que l'index n'est pas encore rempli.

## Module Notifications

Contrats exposes :

- `NotificationDispatcher`
- `DomainEventPublisher`
- `OutboxDispatcher`

Le module contient une outbox centrale `domain_outbox_messages` et la commande `ticket:dispatch-outbox`. Cette couche prepare la publication vers un bus externe si un module devient micro-service.

## Roadmap d'extraction

### Phase 1 - Frontiere stable

- autoload package `Ticket\Payments`
- autoload packages `Ticket\Tenancy`, `Ticket\Ticketing`, `Ticket\PublicCatalog`, `Ticket\Notifications`
- provider de modules configurable
- bindings metier deplaces depuis `AppServiceProvider` vers les providers de modules
- contrats des modules actifs
- adaptateurs Laravel
- controles d'architecture par tests

### Phase 2 - Migration interne du metier

- deplacer les regles de statut paiement dans `packages/payments/src/Domain`
- deplacer le registre des modules publics dans `packages/public-catalog/src/Domain`
- remplacer les validations inline des controleurs API par des `FormRequest`
- separer les routes API par surface : `public`, `platform`, `tenant`, `webhooks`
- ajouter des tests de contrat API pour auth, tenancy et routes publiques
- deplacer le pricing pur dans `packages/payments/src/Domain`
- deplacer les workflows checkout/remboursement dans `Application`
- garder Eloquent, HTTP Paystack et Filament dans `Infrastructure/Laravel`
- ajouter DTOs pour les payloads importants
- deplacer les services tenant/ticketing/public vers `Domain` et `Application`
- ajouter la documentation OpenAPI dans `docs/api/openapi.json`
- ajouter une outbox d'evenements metier pour preparer les micro-services
- rendre les migrations/tests de package decouvrables

Etat actuel : la phase 2 est finalisee pour le monolithe modulaire. Les services applicatifs `Payments`, `PublicCatalog`, `Tenancy`, `Ticketing` et `Notifications` vivent dans les packages. Les anciennes classes `App\Services\Payments`, `App\Services\Public` et `App\Services\Tenancy` restent comme wrappers pour eviter une rupture immediate.

### Phase 3 - Modules metier specialises

- `ticketing` : offres, commandes, recus, access passes, check-in
- `tenancy` : lifecycle tenant, provisioning, stockage tenant
- `public-catalog` : projections publiques, recherche, pages publiques
- `notifications` : notifications tenant/platform, emails, evenements
- `training` : inscriptions, presences, certificats
- `crowdfunding` : campagnes, contributions, progression
- `calls-for-projects` : formulaires, candidatures, evaluation
- `stands` : reservations, exposants, salons

### Phase 4 - Extraction micro-service possible

Un module peut devenir micro-service si :

- ses contrats sont stables
- les appels synchrones sont limites
- les events metier sont explicites
- ses tables et migrations sont isolees
- ses tests peuvent tourner sans le reste du monolithe
