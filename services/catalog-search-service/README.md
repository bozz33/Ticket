# Catalog Search Service

Service NestJS satellite pour decharger le front public du monolithe Laravel.

## Responsabilites

- servir les listings publics multi-modules;
- appliquer filtres, tri, pagination et suggestions;
- porter les projections publiques issues de Laravel;
- exposer les menus/pages CMS publics caches;
- produire sitemap et endpoints lecture seule rapides.

## Principe

Laravel reste la source d'ecriture. Ce service possede ses tables de projection et les met a jour par events ou endpoint d'upsert pendant la phase de transition.
