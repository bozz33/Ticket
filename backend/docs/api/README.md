# API Documentation

Le contrat OpenAPI principal est `openapi.json`.

Il est genere depuis les routes Laravel et documente :

- les surfaces `Public`, `Public Tenant`, `Tenant`, `Platform`, `Webhooks` et `Health`;
- les parametres de chemin;
- les routes protegees par bearer token;
- les reponses d'erreur standardisees par `App\Http\Responses\ApiResponse`.

Le fichier peut etre ouvert dans Swagger UI, Redoc ou importe dans Postman.

Commande de verification utile :

```bash
php artisan route:list --path=api
```
