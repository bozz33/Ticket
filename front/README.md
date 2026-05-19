# Ticket Front

Front public Next.js du projet `Ticket`.

## Objectif

Ce front implémente un portail public unifié, inspiré visuellement de Boleto et des parcours catalogue de Tikerama, tout en gardant une architecture originale et compatible avec le backend Laravel multi-tenant existant.

## Principes de l'implémentation

- App Router Next.js
- pages publiques SSR dynamiques
- routes métiers par module
- couche de données capable d'utiliser l'API backend si elle est disponible
- fallback mock structuré tant que le catalogue public global n'est pas encore exposé côté backend
- endpoints API internes Next pour le catalogue public, les organisateurs et les suggestions de recherche

## Variables d'environnement

Copier `.env.example` vers `.env.local` puis ajuster si besoin.

- `NEXT_PUBLIC_API_BASE_URL`: base URL du backend Laravel
- `NEXT_PUBLIC_SITE_URL`: URL publique du front Next.js
- `NEXT_PUBLIC_PLATFORM_NAME`: nom public de la plateforme
- `NEXT_PUBLIC_ACCOUNT_URL`: URL du panel user
- `NEXT_PUBLIC_ORGANIZER_CTA_URL`: URL d'onboarding organisateur
- `NEXT_PUBLIC_OBSERVABILITY_ENABLED`: active le reporting Web Vitals/erreurs client
- `OBSERVABILITY_ENDPOINT`: collecteur serveur optionnel pour les événements client
- `OBSERVABILITY_TOKEN`: token optionnel envoyé au collecteur

## Routes couvertes

- `/`
- `/evenements`
- `/evenements/[slug]`
- `/formations`
- `/formations/[slug]`
- `/stands`
- `/stands/[slug]`
- `/appels-a-projets`
- `/appels-a-projets/[slug]`
- `/crowdfunding`
- `/crowdfunding/[slug]`
- `/organisateurs/[slug]`
- `/recherche`
- `/checkout/[module]/[slug]`
- `/categories`
- `/villes`
- `/support`
- `/devenir-organisateur`
- `/compte`

## Démarrage

```bash
npm install
npm run dev
```

## Qualité

```bash
npm run lint
npm run a11y
npm run perf:budget
npm run contract:api
npm run typecheck
npm test
npm run smoke:http
npm run e2e:smoke
npm run build
```

`npm run smoke:http` vérifie les pages publiques principales contre `http://127.0.0.1:3000` par défaut. Définir `FRONT_SMOKE_BASE_URL` pour cibler un autre environnement.

`npm run build:ci` lance d'abord `tsc`, puis un build Next/Webpack. `npm run build:restricted` est réservé aux postes locaux très verrouillés: il désactive le worker webpack et écrit dans `.next-build-restricted`.

Les routes privées (`/compte`, `/checkout`, `/verifier`, `/api`) ne doivent pas être mises en cache par le service worker. Les dépendances sont figées à des versions exactes pour rendre les builds reproductibles.

La checklist de release front est documentée dans `docs/production-readiness.md`.
