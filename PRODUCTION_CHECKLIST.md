# Checklist production Ticket

## Variables d’environnement backend

Configurer un `.env` production à partir de `backend/.env.example` avec au minimum :

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` générée
- `APP_URL` URL HTTPS backend
- `PUBLIC_FRONTEND_URL` URL HTTPS frontend
- `QUEUE_CONNECTION=database` ou un driver queue supervisé
- `CACHE_STORE=database`, `redis` ou équivalent persistant
- `SESSION_SECURE_COOKIE=true` en HTTPS
- `CORS_ALLOWED_ORIGINS` limité aux domaines front autorisés
- `BACKUP_PATH` ou un répertoire de sauvegardes disponible

## Base de données

Vérifier les connexions centrale et tenant :

```bash
php artisan ticket:migrate-central
php artisan ticket:migrate-tenant
```

## Catalogue public

Après migrations, import de données ou changement de règles de publication :

```bash
php artisan ticket:rebuild-public-catalog
```

Les contenus terminés sont masqués par défaut du catalogue public. Les APIs peuvent récupérer les archives avec `include_past=true`.

## Vérification production

Exécuter :

```bash
php artisan ticket:production-check
```

La commande doit terminer sans échec bloquant avant mise en ligne.

## Queue et scheduler

Prévoir un worker queue supervisé :

```bash
php artisan queue:work --queue=default
```

Prévoir l’exécution du scheduler Laravel toutes les minutes :

```bash
php artisan schedule:run
```

## E-mails SMTP

Le SMTP global est configurable dans le panel super-admin via le paramètre `mail.smtp`.

Champs attendus :

- serveur SMTP
- port
- chiffrement `tls`, `ssl` ou aucun
- utilisateur
- mot de passe
- adresse expéditeur
- nom expéditeur

Depuis l’édition du paramètre `mail.smtp`, utiliser l’action `Tester SMTP` pour envoyer un e-mail de test à l’adresse expéditeur configurée.

## Paiements

Configurer les clés gateway avant activation réelle :

- `PAYSTACK_PUBLIC_KEY`
- `PAYSTACK_SECRET_KEY`
- `PAYSTACK_WEBHOOK_SECRET`

Vérifier aussi les logs paiements et sécurité configurés dans `.env`.
