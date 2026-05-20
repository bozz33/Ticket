# Plan billetterie événement dédiée, migration Offer et form builder

## Objectif

Ce document décrit le plan d'évolution du projet Ticket vers une billetterie événement dédiée, un form builder réutilisable et une architecture front/back plus modulaire.

L'objectif n'est pas de faire un changement brutal qui casse les paiements existants. L'objectif est de migrer progressivement vers un vrai domaine métier de billetterie, tout en gardant le système utilisable pendant chaque étape.

## Problème actuel

Aujourd'hui, les tickets d'événements sont représentés par le modèle générique `Offer`.

`Offer` est utile car il permet déjà :

- de vendre une offre liée à un événement, une formation, un stand ou un autre module ;
- de gérer un prix ;
- de gérer `quantity_total` et `quantity_sold` ;
- de lancer un paiement ;
- de générer des commandes ;
- de générer des access passes.

Mais `Offer` n'est pas assez clair pour une vraie billetterie événement.

Les limites principales sont :

- le vocabulaire est trop générique pour les organisateurs ;
- il n'y a pas de vrai module métier `Ticket` côté panel organisateur ;
- les tickets d'événement ne sont pas clairement séparés des autres types d'offres ;
- le front ne présente pas encore une expérience billetterie complète ;
- la disponibilité des tickets n'est pas assez visible côté public ;
- l'évolution vers des règles avancées de billetterie serait difficile si tout reste dans `Offer`.

## Pourquoi ne pas supprimer `Offer` immédiatement

Supprimer `Offer` directement serait risqué, car plusieurs flux critiques l'utilisent déjà.

`Offer` est actuellement utilisé par :

- le checkout public ;
- les options de paiement ;
- l'initialisation Paystack ;
- la vérification de paiement ;
- les commandes ;
- les reçus ;
- les access passes ;
- les limites d'achat ;
- les contrôles anti-survente ;
- les modules non événementiels comme formation, stand, crowdfunding ou appel à projet.

Donc une suppression directe casserait probablement le paiement ou les commandes.

## Peut-on migrer vers la billetterie sans casser ?

Oui.

La bonne stratégie est une migration progressive avec compatibilité temporaire.

On introduit un vrai modèle métier `EventTicket`, puis on garde un pont avec `Offer` pendant la transition.

Cela permet :

- d'avoir une vraie billetterie événement ;
- de garder les paiements actuels fonctionnels ;
- de migrer le front étape par étape ;
- de tester chaque lot ;
- de supprimer progressivement la dépendance directe à `Offer` pour les événements quand tout est prêt.

## Choix recommandé

### Créer un modèle `EventTicket`

Le modèle `EventTicket` devient la source métier des tickets d'événement.

Il doit représenter :

- le nom du ticket ;
- le type de ticket ;
- le prix ;
- la devise ;
- le stock total ;
- le stock vendu ;
- le stock réservé temporairement ;
- les limites par commande ;
- les limites par compte ;
- les dates d'ouverture et fermeture des ventes ;
- l'état actif/inactif ;
- l'ordre d'affichage ;
- des métadonnées avancées.

### Garder un lien temporaire vers `Offer`

Pour éviter de casser le checkout existant, `EventTicket` peut garder un champ nullable `offer_id`.

Cela donne :

```txt
EventTicket = modèle métier billetterie
Offer = adaptateur paiement temporaire
```

Pendant la transition :

- l'organisateur crée un `EventTicket` ;
- le système peut créer ou synchroniser une `Offer` technique ;
- le checkout continue à recevoir un identifiant compatible ;
- les commandes et access passes continuent à fonctionner ;
- le front peut progressivement consommer `eventTickets` au lieu de `tiers`.

### Pourquoi cette approche est meilleure

Elle évite un big bang.

Elle permet de livrer par étapes :

- étape 1 : modèle et tests ;
- étape 2 : panel organisateur ;
- étape 3 : API publique ;
- étape 4 : front ;
- étape 5 : paiement natif `EventTicket` ;
- étape 6 : nettoyage progressif de l'ancien usage événementiel de `Offer`.

## Migration cible

### Phase 1 : compatibilité

Créer `event_tickets` et garder `offers`.

```txt
events
  hasMany event_tickets

event_tickets
  belongsTo event
  belongsTo offer nullable

offers
  garde le rôle de support paiement existant
```

### Phase 2 : double exposition API

L'API publique expose :

```txt
tiers       ancien format compatible
 tickets     nouveau format métier
```

Le front peut alors migrer sans casser les pages existantes.

### Phase 3 : checkout compatible

Le checkout accepte encore `offer_id`, mais le ticket affiché vient du domaine `EventTicket`.

Plus tard, le checkout pourra accepter :

```txt
item_type = event_ticket
item_id = ...
```

### Phase 4 : paiement polymorphique

Quand tout est stable, les commandes peuvent évoluer de :

```txt
orders.offer_id
```

vers :

```txt
orders.orderable_type
orders.orderable_id
```

À ce moment, `EventTicket` devient directement achetable sans passer par `Offer`.

### Phase 5 : dépréciation contrôlée

`Offer` reste utile pour les autres modules génériques.

Mais pour les événements :

- l'organisateur ne gère plus des `Offer` ;
- il gère des `EventTicket` ;
- `Offer` devient soit un adaptateur interne, soit disparaît du flux événementiel après migration complète.

## Structure proposée pour `event_tickets`

Champs recommandés :

```txt
id
public_id
event_id
offer_id nullable
name
code
description
ticket_type
price_amount
currency_code
quantity_total
quantity_sold
quantity_reserved
min_per_order
max_per_order
max_per_account
sales_start_at
sales_end_at
is_active
sort_order
meta
created_at
updated_at
```

## Disponibilité des tickets

La disponibilité ne doit pas seulement être :

```txt
quantity_total - quantity_sold
```

Pour une billetterie robuste, elle doit devenir :

```txt
quantity_total - quantity_sold - quantity_reserved
```

Cela permet de gérer les paiements en cours.

Statuts publics recommandés :

```txt
available
low_stock
sold_out
sales_not_started
sales_ended
inactive
```

Libellés front recommandés :

```txt
Disponible
Dernières places
Épuisé
Vente bientôt disponible
Vente terminée
Indisponible
```

## Filament v5

La structure Filament doit suivre l'approche v5 :

```txt
Resource mince
Schemas/*Form
Tables/*Table
Pages/List*
Pages/Create*
Pages/Edit*
RelationManagers/*
```

Pour les tickets d'événement :

```txt
app/Filament/Tenant/Resources/EventTickets/EventTicketResource.php
app/Filament/Tenant/Resources/EventTickets/Schemas/EventTicketForm.php
app/Filament/Tenant/Resources/EventTickets/Tables/EventTicketsTable.php
app/Filament/Tenant/Resources/EventTickets/Pages/ListEventTickets.php
app/Filament/Tenant/Resources/EventTickets/Pages/CreateEventTicket.php
app/Filament/Tenant/Resources/EventTickets/Pages/EditEventTicket.php
```

Dans `EventResource`, ajouter une gestion relationnelle :

```txt
app/Filament/Tenant/Resources/Events/RelationManagers/EventTicketsRelationManager.php
```

Ainsi, un organisateur peut gérer les tickets directement depuis l'événement.

## Form builder réutilisable

Le besoin est d'éviter des formulaires statiques codés en dur, notamment pour les appels à projets.

La solution recommandée est un module réutilisable :

```txt
FormDefinition
FormSubmission
```

Avec une relation polymorphique :

```txt
form_definitions.owner_type
form_definitions.owner_id
```

Cela permet d'attacher un formulaire à :

- un appel à projet ;
- un événement ;
- une formation ;
- un stand ;
- une campagne de crowdfunding ;
- une page CMS future.

## Structure proposée pour `form_definitions`

```txt
id
public_id
owner_type nullable
owner_id nullable
name
title
description
submit_label
success_message
status
schema
validation_schema
settings
created_at
updated_at
```

## Structure proposée pour `form_submissions`

```txt
id
public_id
form_definition_id
submitter_user_id nullable
status
data
files
ip_hash nullable
user_agent_hash nullable
submitted_at
reviewed_at nullable
meta
created_at
updated_at
```

## Types de champs initiaux

Le form builder doit d'abord supporter :

- texte ;
- zone de texte ;
- email ;
- téléphone ;
- nombre ;
- date ;
- select ;
- radio ;
- checkbox ;
- groupe de checkbox ;
- pays ;
- ville ;
- fichier ;
- URL ;
- consentement ;
- section.

Ensuite, on pourra ajouter :

- conditions d'affichage ;
- champs répétés ;
- pièces jointes multiples ;
- paiement obligatoire ;
- reçu obligatoire ;
- score/review interne ;
- workflow de validation.

## Form builder côté Filament

Utiliser le composant `Builder` de Filament pour permettre à l'organisateur de construire son formulaire.

Chaque bloc représente un type de champ :

```txt
text_field
textarea_field
email_field
select_field
file_field
consent_field
section
```

Le schéma est stocké en JSON, mais il doit être validé côté serveur avant publication.

## Form builder côté front

Créer un module :

```txt
front/components/dynamic-form/
  DynamicFormRenderer.tsx
  DynamicFormField.tsx
  DynamicFormSection.tsx
  DynamicFormFileField.tsx
  DynamicFormLocationFields.tsx
  DynamicFormPhoneField.tsx
  validation.ts
  types.ts
```

Le front reçoit un schéma public et affiche le formulaire dynamiquement.

Il ne doit pas faire confiance au front pour la validation finale.

La validation finale doit toujours être côté backend.

## Découpages front restants

### Module ticketing

Créer :

```txt
front/components/ticketing/
  AvailabilityBadge.tsx
  TicketTierCard.tsx
  TicketTierList.tsx
  TicketCtaButton.tsx
  TicketPrice.tsx
  helpers.ts
  types.ts
```

Responsabilités :

- afficher les tickets ;
- afficher les badges de disponibilité ;
- désactiver les tickets épuisés ;
- afficher les prix ;
- centraliser les règles UI de billetterie.

### Module event detail

Créer :

```txt
front/components/route/detail/event/
  EventDetailView.tsx
  EventHeroFacts.tsx
  EventTicketingSection.tsx
  EventScheduleSection.tsx
  EventVenueSection.tsx
  EventSpeakersSection.tsx
```

Responsabilités :

- isoler les spécificités événement ;
- éviter que `DetailBlocks` porte toute la logique ;
- préparer des pages événement plus avancées.

### Module checkout

Continuer le découpage :

```txt
front/components/checkout-client/
  CheckoutTicketSummary.tsx
  CheckoutAvailabilityNotice.tsx
  CheckoutPaymentMethods.tsx
  CheckoutBuyerFields.tsx
```

Responsabilités :

- séparer résumé, paiement, acheteur, disponibilité ;
- préparer le checkout polymorphique futur.

### Module call for project application

Continuer le découpage :

```txt
front/components/call-for-project-application/
```

Puis remplacer progressivement les champs statiques par `DynamicFormRenderer`.

## Tests par lot

Chaque lot doit être testé séparément.

### Backend

Commandes principales :

```txt
composer test
php artisan test
php artisan test --filter=EventTicket
php artisan test --filter=DynamicForm
```

### Frontend

Commandes principales :

```txt
npm run typecheck
npm run quality
npm run smoke:http
```

### Tests attendus

Pour `EventTicket` :

- disponibilité avec stock illimité ;
- disponibilité avec stock limité ;
- sold out ;
- low stock ;
- ventes non ouvertes ;
- ventes terminées ;
- limites min/max par commande ;
- limites par compte ;
- réservation temporaire.

Pour le form builder :

- schéma valide ;
- schéma invalide ;
- validation des champs requis ;
- validation email ;
- validation fichier ;
- soumission publique ;
- soumission liée à un appel à projet ;
- compatibilité avec l'ancien `meta.application_form`.

## Ordre d'implémentation recommandé

### Lot 1 : documentation et sécurité Git

- documenter le plan ;
- committer l'état actuel ;
- pousser la branche ;
- éviter de mélanger la documentation avec des changements de code risqués.

### Lot 2 : modèle `EventTicket`

- migration ;
- modèle ;
- relation `Event::tickets()` ;
- service de disponibilité ;
- tests unitaires.

### Lot 3 : Filament billetterie

- resource `EventTicketResource` ;
- relation manager sous événement ;
- formulaires v5 découpés ;
- table v5 découpée ;
- tests et vérifications.

### Lot 4 : API publique

- exposer `tickets` ;
- garder `tiers` temporairement ;
- ajouter les statuts de disponibilité ;
- tests d'API.

### Lot 5 : front ticketing

- créer `components/ticketing` ;
- afficher badges ;
- désactiver les tickets épuisés ;
- isoler section billetterie événement ;
- qualité front.

### Lot 6 : form builder backend

- entités génériques `FormDefinition` et `FormSubmission` ;
- validation serveur de schéma ;
- API publique de formulaire ;
- resource Filament ;
- tests.

### Lot 7 : form builder front

- `DynamicFormRenderer` ;
- composants de champs ;
- soumission publique ;
- remplacement progressif des formulaires hardcodés.

### Lot 8 : checkout polymorphique

- préparer `orderable_type` et `orderable_id` ;
- supporter `EventTicket` directement ;
- garder rétrocompatibilité ;
- migrer les anciennes commandes si nécessaire.

### Lot 9 : nettoyage progressif

- retirer la dépendance front aux `tiers` pour les événements ;
- limiter l'usage de `Offer` aux modules qui en ont encore besoin ;
- nettoyer les anciens chemins quand les tests sont stables.

## État d'implémentation actuel

### Lots terminés

Les lots suivants ont été implémentés et poussés sur la branche de travail :

```txt
26f3901 feat: add dedicated event ticket backend foundation
79b6201 feat: add event ticket filament management
4274fe2 feat: expose event tickets in public catalog
be27673 feat: add event ticketing frontend components
0eb2975 feat: add reusable dynamic form backend foundation
6436bbd feat: sync event tickets with offers and add form builder admin
1b7d373 feat: add public dynamic form API
b7fe68e feat: add reusable dynamic form frontend module
9cc3f79 feat: integrate dynamic forms into call for project applications
a93801f docs: document dynamic form public API contract
c6398fc feat: introduce ticket-first modular checkout
```

### Billetterie événement

Implémenté :

- migration tenant `event_tickets` ;
- modèle `EventTicket` ;
- relation `Event::tickets()` ;
- service `EventTicketAvailabilityService` ;
- synchronisation legacy `EventTicket` vers `Offer` via `EventTicketOfferSyncService` pour migration/backfill uniquement ;
- commande de backfill `ticket:backfill-event-tickets` ;
- resource Filament `EventTicketResource` ;
- relation manager sous événement ;
- exposition API publique `tickets` en conservant `tiers` ;
- composants front `components/ticketing` ;
- badges de disponibilité et CTA désactivés pour les tickets indisponibles.

Le flux checkout événement résout désormais `EventTicket` directement, quote le prix depuis le ticket et ne crée plus d'`Offer` technique pendant la résolution checkout.

### Form builder

Implémenté :

- migration tenant `form_definitions` et `form_submissions` ;
- modèles `FormDefinition` et `FormSubmission` ;
- service `DynamicFormValidator` ;
- service `PublicFormSubmissionService` ;
- API publique :

```txt
GET  /api/v1/public/tenants/{tenant}/forms/{formDefinition}
POST /api/v1/public/tenants/{tenant}/forms/{formDefinition}/submissions
```

- resource Filament `FormDefinitionResource` avec `Builder` ;
- module front `components/dynamic-form` ;
- proxy Next :

```txt
front/app/api/public/forms/[formId]/submissions/route.ts
```

- intégration dans le parcours :

```txt
/appels-a-projets/[slug]/postuler
```

Si un `FormDefinition` publié est lié à un appel à projets, le front utilise `DynamicFormRenderer`.
Sinon, l'ancien wizard de candidature reste utilisé.
- pour les appels à projets, `DynamicFormRenderer` sert uniquement au rendu et à la collecte des champs ;
- la soumission d'une candidature reste stockée dans le domaine métier `CallForProjectSubmission` ;
- `FormSubmission` reste réservé aux formulaires génériques hors domaine métier spécifique ;
- resource Filament de consultation des `FormSubmission` génériques ;
- stockage persistant des fichiers soumis via formulaire dynamique sur le disque local tenant-aware.
- conditions d'affichage dynamiques `visible_if` côté schéma et rendu front ;
- scoring/review simple des candidatures via `meta.review_score` et `meta.review_notes`.

### Découpage modulaire

Backend :

- le découpage principal est déjà organisé en packages métier : `ticketing`, `payments`, `public-catalog`, `tenancy`, `notifications` ;
- `payments` consomme le checkout via `CheckoutItemResolver`, sans dépendre directement du détail `EventTicket` ;
- `ticketing` reste propriétaire des tickets, réservations et access passes ;
- `public-catalog` reste propriétaire de la projection publique.

Frontend :

- un découpage progressif `features/*` est introduit sans déplacement massif risqué ;
- `features/forms` expose les primitives du form builder ;
- `features/ticketing` expose les composants de billetterie ;
- `features/checkout` expose les primitives client/data du checkout ;
- les anciens chemins `components/*` restent compatibles pendant la migration.

### Contrats et tests validés

Backend ciblé :

```txt
php artisan test --filter=EventTicketAvailabilityServiceTest
php artisan test --filter=EventTicketOfferSyncServiceTest
php artisan test --filter=DynamicFormValidatorTest
php artisan test --filter=PublicFormSubmissionServiceTest
php artisan test --filter=PublicDynamicFormApiTest
php artisan test --filter=ApiArchitectureTest
php artisan test --filter=ApiRouteContractTest
```

Frontend :

```txt
npm run quality
npm run build:ci
npm run contract:api
```

Le contrat OpenAPI documente maintenant les endpoints publics du form builder.

### Points techniques corrigés pendant l'implémentation

- l'ordre des routes publiques a été corrigé pour que `/forms/{formDefinition}` ne soit pas capturé par la route générique `/content/{module}/{slug}` ;
- le contrôleur public du form builder résout désormais les formulaires par `public_id` ou `id` ;
- les signatures du contrôleur incluent le paramètre `{tenant}` pour éviter une mauvaise injection de paramètres ;
- le build Next utilise `npm run build:ci`, car le projet force `next build --webpack` avec Next 16 ;
- `optimize:clear` est nécessaire après modification des routes Laravel si un cache de routes est présent.
- les réservations tickets expirées peuvent être libérées par `ticket:release-expired-ticket-reservations` ;
- le scheduler Laravel exécute la libération multi-tenant toutes les cinq minutes.

### Restant réel

Les éléments suivants restent des évolutions futures, non indispensables au fonctionnement actuel :

- suppression progressive des anciens chemins événementiels basés sur `Offer` ;
- endpoints publics explicites de réservation/libération si l'on veut exposer le panier temporaire avant l'initialisation paiement.
- migration progressive des imports front vers `features/*` ;
- extraction future des resources Filament vers Schemas/Tables dédiées pour suivre un style très grand projet.

## Décision finale

La meilleure implémentation est donc :

```txt
EventTicket dédié + compatibilité Offer temporaire + migration progressive vers checkout polymorphique
```

Cette stratégie est plus robuste qu'une suppression immédiate de `Offer`, car elle protège les flux critiques existants tout en construisant une vraie billetterie événement professionnelle.
