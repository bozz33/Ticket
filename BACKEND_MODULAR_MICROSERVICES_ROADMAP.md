# Backend Modular Microservices Roadmap

## Decision

Ticket reste un monolithe modulaire Laravel + Filament pour le coeur SaaS, les panels et l'orchestration. NestJS sera utilise progressivement pour des services satellites specialises lorsque les frontieres de domaine seront stables.

## Horizons

### Horizon 1 - Monolithe modulaire

Objectif : transformer le backend en packages internes solides avant toute extraction reseau.

Packages actifs :

- `payments`
- `tenancy`
- `ticketing`
- `public-catalog`
- `notifications`

Packages extraits ou operationalises :

- `identity-access`
- `reference-data`
- `media-documents`
- `cms`
- `seo`
- `localization`
- `engagement`
- `access-control`
- `finance-accounting`
- `support-observability`
- `form-builder`
- `content-events`
- `content-training`
- `content-stands`
- `content-calls-for-projects`
- `content-crowdfunding`

Etat 2026-05-25 :

- les 16 packages cibles sont autoloades, enregistres et couverts par tests d'architecture;
- les contrats publics sont poses pour identity/access, reference data, CMS, SEO, i18n, media QR, engagement, access control, finance, support/observability, form-builder et les 5 verticales contenu;
- les anciens services `App\Services\...` critiques restent des wrappers de compatibilite;
- les chemins de migrations central/tenant des packages sont decouvrables par `ticket:migration-paths`;
- l'outbox dispose d'une enveloppe d'evenement versionnee compatible avec les payloads existants.

### Horizon 2 - Extraction NestJS progressive

Ordre recommande :

0. `api-gateway-service` - scaffold NestJS ajoute dans `services/api-gateway-service`
1. `notifications-service` - scaffold NestJS ajoute dans `services/notifications-service`
2. `media-service` - scaffold NestJS ajoute dans `services/media-service`
3. `catalog-search-service` - scaffold NestJS ajoute dans `services/catalog-search-service`
4. `analytics-service` - scaffold NestJS ajoute dans `services/analytics-service`
5. `access-checkin-service` - scaffold NestJS ajoute dans `services/access-checkin-service`, a activer selon charge terrain

### Horizon 3 - Decisions case-by-case

Les modules transactionnels lourds restent dans Laravel tant que le cout d'une extraction reseau est superieur au gain operationnel :

- tenant lifecycle
- identity and RBAC
- checkout, orders, receipts
- payment gateways and webhooks
- finance accounting
- content management through Filament

## Non-negotiable rules

1. Chaque package possede ses tables et expose ses contrats.
2. Aucun package ne doit lire directement les tables internes d'un autre package.
3. Les dependances inter-packages passent par `Contracts`, events ou projections.
4. Les events de domaine doivent etre versionnes.
5. L'outbox est le pont unique vers les futurs microservices.
6. Filament parle au monolithe Laravel; le monolithe agrege les services satellites.
7. Les appels inter-services porteront un `X-Tenant-ID` et un correlation id.

## Extraction gates

Un package peut devenir microservice uniquement si :

- ses contrats publics sont stables;
- ses migrations et tests vivent dans son package;
- les appels synchrones entrants et sortants sont faibles;
- ses events de domaine sont documentes;
- son ownership de donnees est clair;
- une strategie de rollback existe.

## Implementation status

Tranche terminee :

- namespaces Composer des packages cibles;
- service providers des packages;
- enregistrement via `config/modules.php`;
- responsabilites documentees par package;
- tests d'architecture pour le scaffold, les bindings, les wrappers legacy et les chemins de migrations;
- contrats applicatifs pour tous les packages transverses et contenu;
- outbox versionnee prete a etre consommee par des microservices satellites.
- premier satellite NestJS notifications pose avec reservation outbox atomique, idempotence, retry et historique local;
- second satellite NestJS media pose avec intentions d'upload, assets, URLs signees, QR, demandes de rendu document et quotas.
- troisieme satellite NestJS catalogue/search pose avec projections publiques, filtres, suggestions, CMS front et sitemap.
- quatrieme satellite NestJS analytics pose avec ingestion events, KPI, dashboards et exports.
- cinquieme satellite NestJS check-in pose avec projection de passes, scan atomique, anti-double, offline batches et supervision.
- gateway NestJS pose avec proxy public/BFF, propagation `X-Tenant-ID`, `X-Correlation-ID` et `Authorization`;
- Laravel dispose d'une configuration `services.microservices.*` et d'un `MicroserviceClientFactory` pour appeler les satellites sans URL hardcodee.
- Laravel dispose d'une commande `ticket:microservices-check` pour auditer les URLs configurees et tester `/health` des satellites actives avant tout branchement metier.
- Laravel propage un token interne optionnel via `MICROSERVICES_INTERNAL_TOKEN` / `MICROSERVICES_INTERNAL_TOKEN_HEADER` pour securiser les appels service-to-service.
- Laravel expose `ticket:outbox-stats` et `ticket:retry-outbox` pour superviser et relancer les messages outbox en echec avec delai.
- Les flux metier critiques commencent a etre branches : `order.paid` et `access_pass.issued` sont publies depuis le fulfilment paiement vers l'outbox; si les satellites sont actives, ces events alimentent `analytics-service` et la projection `/v1/projections/passes/upsert` de `access-checkin-service`.
- Les contenus evenements publies emettent `event.published` et peuvent alimenter `/v1/projections/catalog-items/upsert` de `catalog-search-service` lorsque le satellite est active.
- Les verticales contenu complementaires sont raccordees au meme flux de projection catalogue : formations publiees (`training.session_scheduled`), stands publies (`stand.reserved`), campagnes de crowdfunding publiees (`crowdfunding_campaign.launched`) et appels a projets publies (`call_for_project.opened`).
- Les workflows ticketing complementaires emettent maintenant des events transverses : remboursements demandes/approuves/rejetes et check-in de pass consomme (`access_pass.checked_in`) pour alimenter l'outbox et analytics.

## API, services et gateway

- Les APIs metier restent dans chaque service specialise : `/v1/catalog`, `/v1/uploads`, `/v1/events`, `/v1/scan`, etc.
- Le gateway est un point d'entree public/BFF. Il route, propage le contexte, centralise observabilite/rate-limit/auth edge, mais ne porte pas la logique metier.
- Les panels Filament continuent de parler au monolithe Laravel. Laravel agrege les satellites via `App\Support\Microservices\MicroserviceClientFactory`.
- En production, le front public peut appeler le gateway pour les lectures et workflows publics. Les operations transactionnelles critiques peuvent rester sur Laravel tant que leur extraction n'est pas rentable.

## Modules restants candidats

Le coeur modulaire actuel couvre le scope principal. Les futurs modules possibles sont des modules de maturite operationnelle, pas des manques bloquants :

- `risk-fraud` : scoring fraude, limites, alertes paiement et anti-abus;
- `workflow-approvals` : validations internes, etats multi-acteurs, SLA;
- `marketing-crm` : campagnes, segments, coupons avances, relances;
- `reporting-exports` : rapports comptables/legaux avances si analytics ne suffit plus;
- `webhooks-integrations` : connecteurs sortants vers CRM, ERP, Zapier/Make;
- `billing-subscriptions` uniquement si la plateforme remet des plans/abonnements plus tard.

Reste avant extraction reseau : installer les dependances Node des satellites, executer les migrations DB dediees, configurer `MICROSERVICES_INTERNAL_TOKEN`, utiliser `ticket:microservices-check` en preflight, superviser l'outbox avec `ticket:outbox-stats`, relancer les erreurs avec `ticket:retry-outbox`, deplacer les migrations historiques dans les packages proprietaires et ajouter des tests end-to-end plus profonds par parcours metier complet.
