# Ticketing Module

Le module `ticketing` porte les evenements, documents tenant, commandes, recus, pass d'acces, check-in, likes et audience organisateur.

Les consommateurs applicatifs doivent utiliser les contrats `Ticket\Ticketing\Contracts`.

## Architecture actuelle

Les services applicatifs vivent dans `src/Application` :

- evenements, documents, commandes et recus;
- billetterie evenement (`EventTicket`), disponibilite, stock reserve, pont temporaire vers `Offer`;
- access passes et check-in;
- likes et audience organisateur;
- demandes de remboursement cote acheteur.

## Billetterie evenement

La billetterie est exposee par les contrats `EventTicketInventory` et `EventTicketOfferBridge`, et branche le checkout via l'adaptateur `TicketingCheckoutItemResolver`.

- `EventTicketInventory` calcule les statuts publics (`available`, `low_stock`, `sold_out`, etc.), reserve le stock pendant le checkout, libere les reservations expirees et convertit les reservations en ventes confirmees.
- `EventTicketOfferBridge` garde `Offer` comme adaptateur de paiement pendant la transition afin que les anciens modules continuent de fonctionner.
- Le checkout public peut recevoir un identifiant de ticket directement; l'offre liee est creee ou synchronisee automatiquement.
- Les reservations sont materialisees dans `ticket_reservations`, puis reliees a la commande et au pass lors de la confirmation.
- Les commandes et pass utilisent des colonnes polymorphes (`orderable_*`, `passable_*`) afin que les prochains modules vendables reutilisent le meme flux sans couplage direct.

Les classes historiques `App\Services\Tenancy` liees au ticketing restent uniquement comme wrappers de compatibilite.

Les migrations tenant propres au module peuvent etre placees dans `database/migrations/tenant`.
