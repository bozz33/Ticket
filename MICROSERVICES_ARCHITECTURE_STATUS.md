# Microservices Architecture Status

## Position actuelle

Le backend reste un monolithe modulaire Laravel pour le coeur SaaS, les panels Filament et les transactions critiques. Les services NestJS ajoutes sont des satellites specialisables.

## Separation retenue

| Couche | Role | Etat |
| --- | --- | --- |
| Laravel monolithe | Source transactionnelle, panels, orchestration, modules internes | actif |
| Packages Laravel | Domaines internes reutilisables via contrats | actif |
| Services NestJS | Satellites deployables separement | scaffold pret |
| API Gateway | BFF public et routage vers satellites | scaffold pret |

## APIs

Chaque service possede ses APIs versionnees en `/v1`.

- `api-gateway-service` : `/v1/catalog/*`, `/v1/search/*`, `/v1/front/*`, `/v1/media/*`, `/v1/analytics/*`, `/v1/checkin/*`
- `notifications-service` : consumer outbox + health
- `media-service` : `/v1/uploads/intent`, `/v1/assets/*`, `/v1/qr-codes`, `/v1/documents/render-jobs`
- `catalog-search-service` : `/v1/catalog`, `/v1/search/suggestions`, `/v1/front/*`, `/v1/sitemap.xml`
- `analytics-service` : `/v1/events`, `/v1/kpis/summary`, `/v1/dashboards/overview`, `/v1/exports`
- `access-checkin-service` : `/v1/projections/passes/upsert`, `/v1/scan`, `/v1/offline-batches`, `/v1/events/{eventId}/summary`

## Modules restants possibles

Pas de module bloquant reste pour le scope actuel. Les candidats futurs sont :

- risk/fraud
- workflow approvals
- marketing CRM
- reporting exports avance
- webhooks integrations
- billing/subscriptions si les plans reviennent

## Etat d'execution locale

Les fichiers de service, migrations SQL, Dockerfiles, Docker Compose et scripts PowerShell sont poses.

L'installation npm a ete tentee, mais l'environnement courant bloque le telechargement npm jusqu'au timeout. Les services seront buildables des que `npm install` peut acceder au registre.
