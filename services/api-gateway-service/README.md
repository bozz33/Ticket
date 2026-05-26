# API Gateway Service

Point d'entree public/BFF des services satellites.

## Role

- exposer une API stable au front public;
- router les lectures publiques vers `catalog-search-service`;
- router les operations media, analytics et check-in vers les services specialises;
- propager `X-Tenant-ID`, `X-Correlation-ID` et `Authorization`;
- garder Laravel comme orchestrateur Filament et source transactionnelle.

## Important

Le gateway ne doit pas contenir de logique metier lourde. Il applique auth, rate-limit, observabilite et orchestration simple.
