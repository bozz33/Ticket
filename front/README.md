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
