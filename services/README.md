# Microservices

Ce dossier contient les services satellites qui peuvent etre deployes hors du monolithe Laravel.

## Regles

- Laravel reste le coeur operationnel et l'agregateur des panels Filament.
- Les services satellites consomment des events versionnes ou exposent des APIs versionnees.
- Aucun panel Filament ne parle directement a un microservice.
- Chaque service possede sa base de donnees applicative quand il stocke son historique.
- Le contexte tenant doit etre transporte via metadata d'event ou header `X-Tenant-ID`.

## Services

- `api-gateway-service` : point d'entree public/BFF qui route vers les services satellites et propage tenant/correlation id.
- `notifications-service` : premier service extractible. Il consomme l'outbox centrale et orchestre email, SMS et notifications in-app.
- `media-service` : service cible pour uploads, URLs signees, QR, rendus PDF/documents et quotas media par tenant.
- `catalog-search-service` : service cible pour listings publics, recherche, suggestions, pages CMS publiques et sitemap.
- `analytics-service` : service cible pour ingestion d'evenements, KPI, dashboards et exports analytiques.
- `access-checkin-service` : service cible pour scan QR, validation de passes, controle anti-double et supervision terrain.

## Execution locale

Installer les dependances de tous les services :

```powershell
powershell -ExecutionPolicy Bypass -File services/scripts/install-service-dependencies.ps1
```

Lancer la stack satellite :

```powershell
docker compose -f services/docker-compose.microservices.yml up --build
```

En local Docker, le polling de l'outbox Laravel par `notifications-service` est desactive par defaut. Il faut le reactiver uniquement quand la base centrale Laravel est accessible depuis Docker via `OUTBOX_DATABASE_URL`.

Lancer les tests des services :

```powershell
powershell -ExecutionPolicy Bypass -File services/scripts/run-service-tests.ps1
```

Verifier la configuration Laravel des satellites :

```powershell
php artisan ticket:microservices-check
```

Quand un service est active via `.env`, la commande appelle aussi son endpoint `/health` en propageant le contexte tenant si fourni :

```powershell
php artisan ticket:microservices-check --only=catalog_search --tenant=demo
```
