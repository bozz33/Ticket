# Notifications Service

Service NestJS satellite pour isoler l'envoi des notifications et preparer la sortie de l'outbox Laravel vers un bus externe.

## Responsabilites

- consommer `domain_outbox_messages` produit par Laravel;
- normaliser les payloads avec enveloppe `_event`;
- appliquer une idempotence par `event_id`;
- router les notifications vers email, SMS et in-app;
- journaliser les livraisons dans une base propre au service;
- permettre plus tard le remplacement de la lecture DB par NATS/Kafka sans changer les handlers.

## Flux

```text
Laravel packages -> domain_outbox_messages -> notifications-service -> channels
```

## Commandes

```bash
npm install
npm run start:dev
npm run build
```

## Variables

Copier `.env.example` vers `.env`.

- `OUTBOX_DATABASE_URL` : base centrale Laravel en lecture/ecriture outbox.
- `NOTIFICATIONS_DATABASE_URL` : base propre du service.
- `OUTBOX_POLL_ENABLED` : `true` pour activer le polling.
- `OUTBOX_BATCH_SIZE` : taille de batch.
- `OUTBOX_MAX_ATTEMPTS` : nombre maximal de tentatives avant statut `failed`.
- `OUTBOX_RETRY_DELAY_SECONDS` : delai de replanification apres echec.
- `OUTBOX_PROCESSING_TIMEOUT_SECONDS` : delai de reprise d'un message bloque en `processing`.

## Robustesse outbox

Le consumer reserve les lignes avec `FOR UPDATE SKIP LOCKED`, passe les messages en `processing`, puis les marque `published` uniquement apres livraison et journalisation locale. En cas d'echec, le message est replanifie en `pending` jusqu'au nombre maximal de tentatives, puis bascule en `failed`.

## Etat

Squelette production-ready de Phase 3. Le service est pret a etre installe/deploye, mais il n'est pas encore branche dans la stack locale par defaut.
