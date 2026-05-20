# Payments Module

Le module `payments` est le premier bounded context extrait du backend.

## Responsabilites

- options de paiement publiques
- initialisation checkout
- verification de paiement
- webhooks gateway
- calcul de frais et pricing
- remboursements
- reversements
- synchronisation de l'etat tenant apres operations financieres

## Ports publics

- `Ticket\Payments\Contracts\CheckoutManager`
- `Ticket\Payments\Contracts\CheckoutItemResolver`
- `Ticket\Payments\Contracts\PaymentWebhookReceiver`
- `Ticket\Payments\Contracts\PricingEngine`
- `Ticket\Payments\Contracts\RefundManager`
- `Ticket\Payments\Contracts\PayoutManager`
- `Ticket\Payments\Contracts\SettlementWorkflow`
- `Ticket\Payments\Contracts\TenantRefundManager`

## Architecture actuelle

Les workflows applicatifs vivent dans `src/Application`.

Les classes `App\Services\Payments` ne sont plus le coeur metier ; elles restent uniquement comme wrappers de compatibilite pour les anciens imports et les tests historiques. Les nouveaux consommateurs doivent passer par les contrats publics ou par les services applicatifs du package.

Le checkout ne depend plus d'un seul type de produit. Le contrat `CheckoutItemResolver` transforme un identifiant public en item payable, pose une reservation optionnelle, puis confirme ou libere cette reservation. Chaque module peut donc brancher son propre stock sans que `payments` connaisse ses tables internes.

Les adaptateurs `src/Infrastructure/Laravel` connectent les contrats aux services `Application`, tandis que les constantes et regles partagees restent dans `src/Domain`.
