# Tenancy Module

Le module `tenancy` porte le cycle de vie des organisations : provisioning, activation, suspension, archivage, suppression, stockage tenant, synchronisation de referentiels, profil public et settings tenant.

Les consommateurs applicatifs doivent utiliser les contrats `Ticket\Tenancy\Contracts`.

## Architecture actuelle

Les services applicatifs vivent dans `src/Application` :

- provisioning tenant et creation admin;
- lifecycle tenant;
- profil public et settings;
- stockage tenant;
- synchronisation des referentiels centraux.

Les classes historiques `App\Services\Tenancy` restent uniquement comme wrappers de compatibilite.

Les migrations propres au module peuvent etre placees dans :

- `database/migrations/central`
- `database/migrations/tenant`
