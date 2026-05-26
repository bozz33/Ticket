# Analytics Service

Service NestJS satellite pour isoler les lectures analytiques du runtime metier.

## Responsabilites

- ingerer les events business versionnes;
- produire des KPI par jour, tenant, module et type d'evenement;
- exposer des donnees de dashboards sans requeter les tables transactionnelles;
- preparer les exports CSV/Excel/PDF.

## Evolution

Le stockage Postgres pose ici est suffisant pour la phase d'extraction. Si la volumetrie augmente, ce service peut migrer vers TimescaleDB ou ClickHouse sans changer les contrats exposes au monolithe.
