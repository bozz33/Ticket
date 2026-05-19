# Ticketing Module

Le module `ticketing` porte les evenements, documents tenant, commandes, recus, pass d'acces, check-in, likes et audience organisateur.

Les consommateurs applicatifs doivent utiliser les contrats `Ticket\Ticketing\Contracts`.

## Architecture actuelle

Les services applicatifs vivent dans `src/Application` :

- evenements, documents, commandes et recus;
- access passes et check-in;
- likes et audience organisateur;
- demandes de remboursement cote acheteur.

Les classes historiques `App\Services\Tenancy` liees au ticketing restent uniquement comme wrappers de compatibilite.

Les migrations tenant propres au module peuvent etre placees dans `database/migrations/tenant`.
