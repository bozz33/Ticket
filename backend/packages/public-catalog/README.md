# Public Catalog Module

Le module `public-catalog` porte le catalogue public, les pages front CMS et les candidatures publiques d'appels a projets.

Les consommateurs applicatifs doivent utiliser les contrats `Ticket\PublicCatalog\Contracts`.

## Read model central

Les listes globales multi-tenants utilisent une projection centrale `public_catalog_items` lorsque l'index est construit. Cela evite de scanner tous les tenants a chaque requete publique.

Commande de rebuild :

```bash
php artisan ticket:rebuild-public-catalog
php artisan ticket:rebuild-public-catalog {tenant-slug}
```

Si la table ou les donnees de projection ne sont pas encore disponibles, le service garde un fallback vers la lecture historique multi-tenants afin de rester compatible pendant les migrations.
