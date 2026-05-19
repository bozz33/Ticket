# Front Production Readiness

## Commandes obligatoires

```bash
npm run quality
npm audit --audit-level=moderate
npm run smoke:http
npm run build
npm run e2e:smoke
```

`npm run smoke:http` cible `http://127.0.0.1:3000` par défaut et accepte `FRONT_SMOKE_BASE_URL` pour valider un environnement de recette.

Sur un poste local qui bloque la création de processus enfants, utiliser `npm run build:restricted` uniquement comme diagnostic. La validation release reste `npm run build:ci` ou `npm run build` dans un environnement CI standard.

## QA manuelle avant release

- Desktop 1440px : `/`, `/evenements`, `/recherche`, `/checkout/[module]/[slug]`, `/compte/connexion`.
- Mobile 390px : menu, recherche, cards catalogue, formulaires auth, checkout.
- Auth : login erreur, inscription, reset password, redirection vers `/compte/connexion` quand non connecté.
- Checkout : options de prix, quantité min/max, paiement gratuit, paiement Paystack, succès, reçu.
- Compte : accueil, commandes, passes, reçus, profil, remboursement.
- PWA : login puis logout, vérifier qu’aucune réponse `/compte`, `/checkout`, `/verifier` ou `/api` n’est servie depuis Cache Storage.

## Observabilité

Activer en production :

```env
NEXT_PUBLIC_OBSERVABILITY_ENABLED=true
OBSERVABILITY_ENDPOINT=https://observability.example.com/client-events
OBSERVABILITY_TOKEN=...
```

Sans endpoint, `/api/observability/client-event` accepte les événements mais ne les transmet pas à un collecteur externe.

## E2E backend complet

Les tests `e2e/backend-flows.spec.ts` sont désactivés par défaut pour éviter les faux rouges sans données seedées. Les activer avec :

```bash
E2E_FULL_BACKEND=1 E2E_TENANT=demo-front-buyer E2E_CONTENT_MODULE=evenements E2E_CONTENT_SLUG=summit-demo-free-2026 npm run e2e
```

Ajouter `E2E_ORDER_REF` et `E2E_RECEIPT_REF` pour couvrir commande, remboursement et reçu.
