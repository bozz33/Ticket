# Media Service

Service NestJS satellite pour isoler les medias lourds du monolithe Laravel.

## Responsabilites

- creer des intentions d'upload avec contexte tenant;
- cataloguer les assets et leurs metadonnees;
- produire des URLs signees pour consultation ou telechargement;
- generer des QR codes standardises pour recus, tickets et passes;
- enregistrer les demandes de generation PDF/document;
- appliquer des quotas par tenant.

## Frontieres

Laravel reste l'orchestrateur Filament et continue d'appeler ce service via un client applicatif. Les tables du service media ne sont pas lues directement par Laravel.

## Commandes

```bash
npm install
npm run start:dev
npm run build
```

## Migration

Executer `database/migrations/001_create_media_tables.sql` sur la base `MEDIA_DATABASE_URL`.
