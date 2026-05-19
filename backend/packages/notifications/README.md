# Notifications Module

Le module `notifications` isole l'envoi de notifications applicatives et la publication d'evenements metier.

## Ports publics

- `NotificationDispatcher`
- `DomainEventPublisher`
- `OutboxDispatcher`

## Outbox

Les evenements metier sont persistés dans la table centrale `domain_outbox_messages`.

Commande de dispatch :

```bash
php artisan ticket:dispatch-outbox --limit=100
```

Cette couche prepare une extraction microservice : les producteurs ecrivent dans l'outbox, puis un worker peut publier les messages vers un bus externe plus tard.
