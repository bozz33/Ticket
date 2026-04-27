# Architecture des Données Complète du Projet Ticket

## 1. Objet du document

 Ce document définit l’architecture complète des données du projet `Ticket`.
 
 Il complète le document `ARCHITECTURE_COMPLETE_PROJET.md` en détaillant :
 
 - la séparation des données entre la plateforme centrale et les tenants
 - la source de vérité de chaque domaine métier
 - les projections publiques nécessaires au portail unifié
 - les règles d’identification, d’intégrité et de synchronisation
 - les contraintes liées à la sécurité, aux paiements, aux médias et aux audits
 - l’alignement logique avec la couche technique détaillée décrite dans `CONCEPTION_TECHNIQUE_DETAILLEE_COMPLETE.md`
 - les implications de paiement détaillées décrites dans `INTEGRATION_PAYSTACK_COMPLETE.md`
 
 Ce document ne décrit pas le schéma SQL final ligne par ligne, mais la structure logique cible qui servira de base à la modélisation applicative et aux futures migrations.

---

## 2. Principes directeurs

L’architecture des données repose sur les principes suivants.

### 2.1 Isolation métier par tenant

Chaque organisation dispose de sa propre base de données opérationnelle.

Cette base contient ses données métier propres :

- événements
- billets
- commandes
- scans
- formations
- appels à projets
- crowdfunding
- stands
- utilisateurs internes au tenant
- journaux opérationnels du tenant

### 2.2 Portail public unique

Le frontend public est unique.

Il ne va pas interroger en direct toutes les bases tenants à chaque requête publique. Pour cela, il s’appuie sur des projections publiques centralisées stockées dans la base plateforme.

### 2.3 Base centrale limitée mais stratégique

La base centrale ne doit pas devenir une copie complète des bases tenants.

Elle doit contenir uniquement :

- les données de gouvernance plateforme
- les métadonnées d’identification publique
- les données nécessaires au routage
- les index et projections publiques
- les éléments globaux de finance, supervision, modération et support

### 2.4 Source de vérité explicite

Chaque donnée doit avoir une source de vérité claire.

Exemple :

- le prix d’un billet est vrai dans la base tenant
- le résumé public du billet ou de l’événement est projeté dans la base centrale
- une modification côté tenant doit mettre à jour la projection centrale

### 2.5 Écriture locale, lecture publique optimisée

Les opérations métier se font dans le tenant.

Les lectures publiques à fort trafic se font dans le central via des projections.

### 2.6 Identifiants stables et traçables

Les entités publiques doivent pouvoir être liées de façon fiable entre :

- base centrale
- base tenant
- frontend public
- événements applicatifs
- services de paiement
- exports et audits

---

## 3. Niveaux de données

L’architecture comporte trois couches logiques de données.

## 3.1 Couche A : Données plateforme centrales

Elles vivent dans la base `landlord`.

Elles servent à :

- gérer l’écosystème de tenants
- exposer les contenus publics
- piloter la plateforme
- modérer les publications
- suivre les flux financiers globaux
- fournir des APIs publiques et des parcours publics rapides

## 3.2 Couche B : Données opérationnelles tenant

Elles vivent dans une base dédiée par tenant.

Elles servent à :

- créer et gérer les contenus métier
- exécuter les opérations transactionnelles
- gérer les équipes locales
- stocker les détails sensibles et internes

## 3.3 Couche C : Données dérivées / projections / analytics

Elles peuvent vivre selon les cas :

- dans la base centrale pour les projections publiques
- dans des tables dédiées d’agrégats
- dans des snapshots analytiques
- dans un moteur de recherche si introduit plus tard

Cette couche ne remplace jamais la donnée source.

---

## 4. Stratégie d’identification des entités

## 4.1 Règle générale

Chaque entité importante doit avoir au moins deux identifiants :

- un identifiant technique interne
- un identifiant public stable si l’entité est exposée

## 4.2 Types d’identifiants recommandés

### Identifiant technique

- UUID ou ULID recommandé pour les entités majeures
- entier auto-incrémenté possible localement pour certains besoins internes, mais pas comme identifiant d’échange inter-systèmes

### Identifiant public

- `public_id` ou UUID exposable
- `slug` lisible pour SEO et partage

## 4.3 Clés de rattachement indispensables

Pour toute ressource projetée dans le central, il faut conserver :

- `tenant_public_id`
- `tenant_internal_reference` si nécessaire
- `source_entity_public_id`
- `source_entity_type`
- `source_updated_at`
- `projection_updated_at`
- `publication_status`

## 4.4 Références externes

Les entités financières ou intégrées doivent aussi pouvoir stocker :

- `gateway_reference`
- `provider_reference`
- `external_customer_reference`
- `external_payout_reference`

---

## 5. Domaine central : données stockées dans la base plateforme

Cette section décrit les grands ensembles de données qui doivent exister côté central.

# 5.1 Référentiel tenant

## Finalité

Identifier chaque organisation, son état, sa présence publique et son rattachement technique.

## Entités logiques

### Tenant

Contient :

- identifiant du tenant
- nom interne
- nom public
- statut
- date d’activation
- plan éventuel
- devise par défaut
- pays principal
- timezone
- configuration générale minimale

### Tenant Public Profile

Contient :

- tenant public id
- slug public
- logo
- bannière
- description courte
- description longue
- contacts publics
- réseaux sociaux
- ville / pays
- statut vérifié
- paramètres visuels limités

### Tenant Domain Mapping

Contient :

- domaine principal éventuel
- sous-domaine éventuel
- type de résolution d’URL
- état SSL ou configuration DNS si géré plus tard

### Tenant Lifecycle

Contient :

- état du tenant
- date de création
- date d’activation
- date de suspension
- motif de suspension
- date d’archivage éventuelle

# 5.2 Utilisateurs plateforme

Ce domaine concerne uniquement les comptes internes à la plateforme centrale.

### Entités

- platform_user
- platform_role
- platform_permission
- platform_user_role
- platform_audit_actor

# 5.3 Référentiels globaux

Référentiels mutualisés utilisables par plusieurs modules.

### Exemples

- catégories globales
- tags globaux normalisés
- pays
- villes de référence à terme
- devises
- langues supportées
- types de ressource publique
- statuts publics normalisés
- types de paiement supportés

# 5.4 Catalogue public central

Ce domaine est stratégique.

Il contient toutes les projections permettant au portail public d’afficher rapidement les contenus publiés.

## Entités logiques majeures

### Public Resource Index

Table ou ensemble de tables servant d’index global multi-type.

Contient :

- type de ressource
- identifiant public
- tenant public id
- slug
- titre public
- résumé
- image principale
- statut public
- dates utiles
- localisation résumée
- url canonique
- visibilité
- date de publication
- score ou priorité de mise en avant

### Public Event Projection

Projection enrichie d’un événement publié.

Contient au minimum :

- event public id
- tenant public id
- slug
- titre
- sous-titre éventuel
- description courte publique
- description détaillée publique
- organisateur résumé
- catégorie
- tags exposables
- image hero
- galerie légère
- ville / pays
- adresse publique
- géolocalisation si utile
- date de début
- date de fin
- fuseau horaire
- indicateur multi-dates
- prix minimum affiché
- prix maximum affiché
- devise
- statut de vente
- statut de publication
- booléen privé/public
- booléen en ligne / présentiel / hybride
- url publique
- seo title / seo description / social image si géré

### Public Organizer Projection

Projection de la page publique organisateur.

Contient :

- tenant public id
- slug
- nom public
- description
- logo
- bannière
- pays / ville
- vérifié ou non
- contacts publics
- réseaux sociaux
- statistiques exposables éventuelles
- compteurs de contenus publiés

### Public Formation Projection

Contient :

- formation public id
- tenant public id
- slug
- titre
- résumé
- type de formation
- prochaine session
- mode présentiel/distanciel
- lieu résumé
- prix minimum
- capacité publique
- statut d’inscription

### Public Call Projection

Contient :

- call public id
- tenant public id
- slug
- titre
- résumé
- date d’ouverture
- date de clôture
- catégories concernées
- conditions résumées
- statut d’ouverture

### Public Campaign Projection

Contient :

- campaign public id
- tenant public id
- slug
- titre
- résumé
- objectif
- montant collecté visible
- devise
- date de fin
- statut de campagne
- image hero

### Public Salon / Stand Projection

Contient :

- salon public id
- tenant public id
- slug
- titre
- résumé
- période
- lieu
- stands disponibles résumés
- statut de réservation

# 5.5 Projection de recherche et navigation

Selon la charge et les besoins, il peut être utile de distinguer :

- l’index public général
- les projections détaillées par type
- une table de recherche dénormalisée
- des facettes de filtre pré-calculées

## Champs fréquents à indexer

- slug
- type
- tenant_public_id
- country_code
- city_name
- start_at
- end_at
- publication_status
- visibility
- category_slug
- searchable_text

# 5.6 Finance globale plateforme

Ce domaine ne remplace pas la comptabilité du tenant, mais trace la vue plateforme.

### Entités logiques

- platform_transaction
- platform_fee_rule
- platform_fee_settlement
- payout_batch
- payout_line
- refund_case
- payment_incident
- gateway_webhook_log

## Informations stockées

- source module
- tenant concerné
- commande ou contribution source
- montant brut
- frais plateforme
- frais passerelle
- montant net
- devise
- statut transactionnel
- statut reversement
- statut remboursement
- références externes

# 5.7 Support, modération et conformité

### Entités centrales

- moderation_case
- moderation_decision
- tenant_verification_file
- support_ticket_platform
- privacy_request
- compliance_event_log

# 5.8 Observabilité et audit central

### Entités ou journaux

- platform_audit_log
- async_job_log
- projection_sync_log
- publication_log
- authentication_log
- critical_error_log

---

## 6. Domaine tenant : données stockées dans chaque base organisation

Cette section décrit la structure logique de la base dédiée d’un tenant.

# 6.1 Noyau tenant

## Tenant Settings Local

Contient les paramètres internes d’exploitation du tenant.

Exemples :

- préférence de devise
- politique de remboursement locale
- paramètres d’emails métier
- paramètres d’impression ou de billets
- préférences de scan
- paramètres de branding local complémentaire

## Tenant Users and Access
 
 ### Entités logiques
 
 - user
 - role
 - permission
 - model_has_roles
 - model_has_permissions
 - role_has_permissions
 - team_membership si structure d’équipes
 - user_invitation
 - session_log
 
 ## Remarque
 
 Les comptes d’administration tenant ne doivent pas être mélangés aux comptes plateforme.
 
 La recommandation technique retenue est d’utiliser `spatie/laravel-permission` comme fondation RBAC dans le tenant, avec intégration Filament via `Filament Shield`.

 # 6.2 Module billetterie côté tenant

## Entités métier principales

### Event

Donnée source de vérité pour l’événement.

Contient :

- identifiant interne
- public id
- slug candidat
- titre interne/public
- description complète
- catégorie locale ou référencée
- type d’événement
- mode de participation
- lieu complet
- consignes d’accès
- statut de brouillon/publication
- statut métier
- visibilité
- dates
- capacité globale
- paramètres de vente
- paramètres SEO éventuels
- horodatages métier

### Event Occurrence / Session

Pour gérer les événements à dates multiples.

Contient :

- event_id
- date de début
- date de fin
- statut de session
- capacité session
- lieu session si variable

### Ticket Type

Contient :

- event_id
- nom du ticket
- description
- prix
- devise
- quota
- quota par commande
- période de vente
- ordre d’affichage
- avantage inclus
- remboursable ou non
- statut

### Order

Contient :

- référence commande
- client id
- canal de vente
- statut de paiement
- statut de commande
- montant total
- frais détaillés
- devise
- coupons appliqués
- données facturation si besoin
- source marketing éventuelle

### Order Item

Contient :

- order_id
- ticket_type_id
- quantité
- prix unitaire
- montant ligne
- taxes ou frais associés

### Attendee / Participant

Contient :

- identité participant
- email
- téléphone
- champs complémentaires demandés
- consentements nécessaires

### Issued Ticket

Contient :

- order_item_id ou attendee_id
- numéro unique
- qr_token ou référence de scan
- statut du ticket
- statut d’entrée
- date d’émission
- date d’annulation éventuelle

### Ticket Scan

Contient :

- ticket_id
- scan_at
- scanner_user_id
- gate_id
- résultat du scan
- motif d’échec éventuel
- device_reference si utile

### Coupon / Promotion

Contient :

- code
- type de réduction
- valeur
- période de validité
- règles d’applicabilité
- plafond d’usage
- nombre d’utilisations

### Refund Request / Refund Operation

Contient :

- ordre ou ticket concerné
- motif
- initiateur
- montant demandé
- montant remboursé
- statut
- horodatages

# 6.3 Module formations côté tenant

### Entités

- training
- training_session
- trainer
- enrollment
- waitlist_entry
- attendance_record
- training_material
- certificate_record à terme

## Données clés

- objectifs pédagogiques
- capacités par session
- conditions d’inscription
- présence et statut de complétion

# 6.4 Module appels à projets côté tenant

### Entités

- call_for_projects
- call_category
- call_form_schema
- application_submission
- application_answer
- application_attachment
- reviewer
- evaluation_grid
- evaluation_score
- review_decision
- application_status_history

## Points clés

- fort besoin de pièces jointes
- workflow de statuts détaillé
- historisation stricte des décisions

# 6.5 Module crowdfunding côté tenant

### Entités

- campaign
- campaign_milestone
- donation / contribution
- donor_profile
- campaign_update
- receipt_record
- beneficiary_info
- refund_or_chargeback_record

## Données clés

- objectif financier
- montant collecté réel
- statut contribution
- visibilité donateur selon choix
- liens avec paiements

# 6.6 Module stands côté tenant

### Entités

- expo_event / salon
- hall
- zone
- stand
- stand_type
- stand_reservation
- exhibitor
- exhibitor_document
- stand_payment_schedule

## Données clés

- disponibilité
- prix selon type et emplacement
- blocage temporaire pendant réservation
- validation organisateur si nécessaire

# 6.7 CMS et contenus tenant

### Entités

- page_content
- faq_entry
- media_asset
- announcement
- banner_slot_content
- legal_content

## Règle

Les contenus purement éditoriaux du tenant restent dans la base tenant si leur gouvernance est locale, mais leurs extraits publics peuvent être projetés au central.

# 6.8 Finance locale tenant

### Entités logiques

- payment_attempt
- payment_confirmation
- tenant_ledger_entry
- invoice_record
- payout_expectation
- local_refund_record
- reconciliation_log

## Rôle

Ces tables donnent la vue opérationnelle et détaillée côté organisation.

# 6.9 Support et opérations locales

### Entités

- support_ticket_tenant
- customer_contact_log
- incident_log
- event_operation_note
- access_gate
- device_registry à terme

# 6.10 Audit local

### Entités

- audit_log
- publication_history
- role_change_log
- sensitive_action_log

---

## 7. Séparation stricte central vs tenant

La séparation doit être explicitement définie pour éviter les doublons et les incohérences.

## 7.1 Ce qui doit rester au central

- identité de plateforme
- identité publique des tenants
- index public transversal
- routage public
- finances globales plateforme
- modération et supervision globales
- logs d’intégration et projections
- référentiels globaux

## 7.2 Ce qui doit rester dans le tenant

- création et édition métier
- détails riches des modules
- fichiers sensibles opérationnels
- données clients complètes
- commandes détaillées
- workflows métiers détaillés
- décisions locales de gestion
- permissions locales

## 7.3 Ce qui peut exister aux deux niveaux

Sous forme source + projection :

- événement
- organisateur public
- formation publique
- appel à projets public
- campagne publique
- salon public

Dans ce cas :

- la source de vérité est dans le tenant
- la représentation publique optimisée est dans le central

---

## 8. Source de vérité par domaine

## 8.1 Tenant comme source de vérité

Le tenant est source de vérité pour :

- événements
- sessions
- billets
- commandes
- participants
- scans
- formations
- inscriptions
- appels à projets
- candidatures
- campagnes
- contributions
- stands
- réservations de stands
- contenu légal local
- documents métier

## 8.2 Central comme source de vérité

Le central est source de vérité pour :

- liste des tenants
- statut global d’un tenant
- slug public d’un tenant
- règles globales plateforme
- frais plateforme
- projection publique diffusée
- modération centrale
- support plateforme
- journaux techniques inter-systèmes

## 8.3 Source partagée mais hiérarchisée

Certains domaines ont une responsabilité partagée :

### Paiements

- tentative et détails opérationnels dans le tenant
- vision consolidée et frais plateforme au central

### Reversements

- attente de reversement visible dans le tenant
- batch de reversement et statut global au central

### Support

- support local dans le tenant
- incidents globaux ou transverses au central

---

## 9. Projections publiques centrales

La projection publique est indispensable pour l’expérience cible.

## 9.1 Objectifs

- listing rapide
- SEO propre
- recherche multi-tenant
- filtres performants
- routage stable
- réduction du coût de lecture cross-tenant

## 9.2 Règles de projection

Chaque projection doit contenir uniquement les champs nécessaires à l’affichage public et à la recherche.

Elle ne doit pas copier tout le modèle source.

## 9.3 Champs minimaux recommandés pour toute projection

- `resource_type`
- `resource_public_id`
- `tenant_public_id`
- `slug`
- `title`
- `summary`
- `hero_image`
- `publication_status`
- `visibility`
- `starts_at`
- `ends_at`
- `country_code`
- `city_name`
- `public_url`
- `searchable_text`
- `source_updated_at`
- `projected_at`

## 9.4 Statuts recommandés

### Côté source

- draft
- pending_review
- published
- unpublished
- archived
- suspended

### Côté projection

- active
- inactive
- outdated
- deleted
- pending_sync
- sync_failed

## 9.5 Politique de suppression

Quand une ressource source est :

- dépubliée
- archivée
- supprimée logiquement
- suspendue par modération

la projection centrale doit être mise à jour rapidement pour la masquer du public.

---

## 10. Flux de synchronisation tenant vers central

## 10.1 Déclencheurs standards

Un flux de synchronisation doit être déclenché sur les événements applicatifs suivants :

- création d’une ressource publiable
- modification d’une ressource déjà publiée
- changement de slug
- changement de statut public
- changement d’image ou d’information visible
- suppression logique
- restauration

## 10.2 Pipeline recommandé

1. modification en base tenant
2. validation métier locale
3. émission d’un domain event
4. mise en file d’un job de projection
5. lecture de la ressource source
6. transformation vers le modèle public
7. upsert dans la base centrale
8. journalisation du résultat

## 10.3 Logs de synchronisation à conserver

- ressource source
- tenant concerné
- type de projection
- version source ou timestamp source
- résultat sync
- message d’erreur éventuel
- nombre de tentatives
- date dernière tentative

## 10.4 Règles de robustesse

- jobs idempotents
- retriable en cas d’échec temporaire
- journal d’erreurs exploitable
- possibilité de resynchronisation manuelle
- commande de rebuild complet par tenant ou par type de ressource

---

## 11. Publication, dépublication, archivage

## 11.1 Publication

Une ressource devient publique uniquement si :

- son statut métier l’autorise
- les champs obligatoires sont remplis
- la modération éventuelle est satisfaite
- sa projection centrale est créée ou mise à jour

## 11.2 Dépublication

La dépublication doit :

- masquer la ressource des listings publics
- préserver l’historique interne
- conserver les commandes et transactions déjà émises

## 11.3 Archivage

L’archivage doit permettre :

- de retirer la ressource des vues actives
- de préserver la donnée à des fins d’historique, finance, audit et reporting

---

## 12. Stratégie utilisateur et comptes

Ce sujet doit être clarifié très tôt.

## 12.1 Types de comptes à distinguer

- compte plateforme interne
- compte administration tenant
- compte public client
- profil participant / candidat / contributeur

## 12.2 Recommandation de modélisation

### Administration

- comptes admins plateforme au central
- comptes admins tenant dans chaque tenant ou via un modèle central fédéré selon la stratégie retenue plus tard

### Public

Pour l’expérience client, il est recommandé de prévoir un compte public unifié côté plateforme, surtout si un même utilisateur peut :

- acheter des billets
- s’inscrire à une formation
- candidater à un appel
- contribuer à une campagne

## 12.3 Données publiques utilisateur

Un compte public unifié peut vivre au central avec :

- identité de compte
- email
- téléphone
- préférences globales
- consentements plateforme

Les données métier liées aux actions réalisées restent ensuite rattachées aux modules concernés.

## 12.4 Précaution

Même avec un compte public central, les données transactionnelles détaillées peuvent être répliquées ou projetées partiellement dans les tenants selon les parcours métier.

---

## 13. Modèle financier des données

## 13.1 Objectif

La plateforme doit tracer les mouvements financiers sans ambiguïté.

## 13.2 Niveaux de stockage

### Côté tenant

Vue détaillée d’exécution :

- tentative de paiement
- confirmation
- commande source
- remboursement local
- pièces justificatives
- ledger opérationnel local

### Côté central

Vue consolidée plateforme :

- commission
- frais
- part nette tenant
- reversement
- incidents passerelle
- rapprochement global

## 13.3 Granularité minimale d’une écriture financière

Chaque écriture importante doit conserver :

- type de flux
- module source
- entité source
- montant brut
- devise
- frais plateforme
- frais passerelle
- net tenant
- statut de règlement
- statut de reversement
- références externes
- timestamps métier et fournisseur

## 13.4 Règles de non-régression

- ne jamais écraser l’historique financier
- privilégier des écritures append-only pour le ledger
- stocker les changements d’état dans des statuts historisés ou journaux dédiés

---

## 14. Documents, médias et fichiers

## 14.1 Types de médias à gérer

- logos tenant
- bannières
- affiches d’événement
- galeries
- e-tickets générés
- pièces jointes candidatures
- documents de formation
- justificatifs administratifs tenant
- reçus et factures

## 14.2 Règle de stockage logique

Le fichier binaire lui-même doit être stocké hors base relationnelle lorsque possible.

La base stocke surtout :

- identifiant du média
- propriétaire logique
- type de ressource propriétaire
- chemin ou clé de stockage
- mime type
- taille
- checksum si utile
- visibilité
- statut antivirus ou validation si ajouté
- métadonnées d’usage

## 14.3 Séparation des médias

Il faut distinguer :

- médias publics diffusables
- documents privés internes tenant
- documents confidentiels de conformité
- pièces justificatives clients ou candidats

## 14.4 Politique de rétention

Les pièces justificatives sensibles doivent suivre des règles de conservation et de suppression explicites.

---

## 15. Audit, traçabilité et historisation

## 15.1 Actions à auditer absolument

- création / publication / dépublication
- changement de prix
- remboursement
- annulation d’événement
- scan manuel ou invalidation
- changement de rôle
- validation ou rejet de candidature
- modification de campagne financière
- décision de modération

## 15.2 Données minimales d’audit

- acteur
- rôle
- contexte tenant ou plateforme
- action
- entité concernée
- ancien état résumé
- nouvel état résumé
- date
- adresse IP ou contexte technique si pertinent

## 15.3 Historisation des statuts

Pour les entités critiques, il est recommandé d’avoir des tables d’historique ou des journaux dédiés pour :

- commandes
- remboursements
- publications
- candidatures
- paiements
- reversements

---

## 16. Indexation, performance et recherche

## 16.1 Champs à indexer souvent côté central

- `slug`
- `resource_public_id`
- `tenant_public_id`
- `publication_status`
- `visibility`
- `starts_at`
- `country_code`
- `city_name`
- `resource_type`

## 16.2 Champs à indexer souvent côté tenant

Selon les modules :

- références de commande
- email client
- qr token
- statut paiement
- statut commande
- date d’événement
- statut candidature
- statut inscription
- statut contribution

## 16.3 Recherche full-text

Si la volumétrie devient importante, la recherche peut évoluer vers :

- un index SQL enrichi au central
- ou un moteur dédié

Mais le modèle de projection doit être prêt dès le départ.

---

## 17. Sécurité et cloisonnement des données

## 17.1 Règle absolue

Une lecture tenant ne doit jamais pouvoir accéder à la donnée d’un autre tenant hors mécanisme explicite de plateforme.

## 17.2 Mesures logiques

- contexte tenant obligatoire côté backoffice
- clés de rattachement explicites
- validation systématique des permissions
- masquage des documents sensibles
- chiffrement sélectif pour certaines données

## 17.3 Données sensibles à traiter avec précaution

- informations personnelles participants
- téléphones et emails
- justificatifs d’identité ou documents administratifs
- données de paiement et références sensibles
- dossiers d’appels à projets

## 17.4 Données à ne pas exposer en projection publique

- données personnelles détaillées
- documents privés
- statuts internes d’investigation
- historique financier détaillé
- notes internes de support

---

## 18. Stratégie de suppression et de rétention

## 18.1 Types de suppression

- suppression logique recommandée pour la plupart des entités métier
- suppression physique seulement pour certains fichiers temporaires ou cas réglementaires précis

## 18.2 Entités à conserver longtemps

- commandes
- paiements
- reversements
- audits
- scans
- candidatures et décisions
- contributions et reçus

## 18.3 Droit à l’effacement

Si une politique de suppression des données personnelles est mise en place, elle doit être pensée sans casser :

- les obligations de preuve
- les obligations financières
- les obligations d’audit

Une anonymisation partielle peut être préférable à une suppression totale dans certains cas.

---

## 19. Synchronisation inverse et agrégats analytiques

## 19.1 Central vers tenant

Le flux principal est tenant vers central pour les projections publiques.

Le central peut toutefois pousser ou exposer vers le tenant :

- référentiels globaux
- règles globales de frais
- décisions de modération
- statuts de reversement

## 19.2 Agrégats analytiques

Les analytics globales peuvent s’appuyer sur :

- snapshots quotidiens
- agrégats par tenant
- agrégats par module
- agrégats par pays, ville, catégorie

## 19.3 Règle

Un agrégat analytique n’est pas la source de vérité.

Il doit être recalculable.

---

## 20. Matrice synthétique central vs tenant

## 20.1 Central

- tenants
- profils publics organisateurs
- slugs et routage public
- projections publiques multi-modules
- modération globale
- finances plateforme
- reversements consolidés
- support plateforme
- audits centraux
- référentiels globaux

## 20.2 Tenant

- événements complets
- billets complets
- commandes détaillées
- participants
- scans QR
- formations et sessions
- candidatures et évaluations
- campagnes et contributions détaillées
- stands et réservations
- équipes et permissions locales
- documents métier
- opérations support locales
- ledger local

## 20.3 Mixte via projection

- événement public
- page organisateur
- formation publique
- appel public
- campagne publique
- salon public

---

## 21. Décisions à figer ensuite

Le présent document pose une architecture cible solide, mais certaines décisions devront être tranchées avant la modélisation physique finale.

### 21.1 Compte public unifié ou non

Il faut décider si le client final a :

- un seul compte global plateforme
- ou des profils implicites par action

### 21.2 Niveau de duplication des données client dans les tenants

Il faut décider si :

- le tenant stocke une copie locale de certains profils clients
- ou s’appuie surtout sur des références centralisées

### 21.3 Granularité exacte du ledger financier

Il faut décider jusqu’où aller sur :

- le détail des écritures
- les statuts de reversement
- les rapprochements passerelle

### 21.4 Moteur de recherche

Il faut décider si la recherche initiale repose sur :

- SQL indexé au central
- ou un moteur externe dès le départ

### 21.5 Stratégie d’authentification des admins tenant

Il faut choisir entre :

- comptes isolés par tenant
- ou fédération d’identité avec rattachement multi-tenant

---

## 22. Recommandation finale

L’architecture des données recommandée pour `Ticket` est la suivante :

- une base centrale légère mais stratégique
- une base dédiée par tenant pour toute la donnée métier opérationnelle
- des projections publiques centralisées pour le portail unique
- une source de vérité explicite par domaine
- une synchronisation pilotée par événements et jobs idempotents
- une séparation stricte entre données publiques, données métier et données sensibles
- un modèle financier traçable à deux niveaux : local tenant et consolidé plateforme

Cette approche permet :

- de préserver l’isolation multi-tenant
- d’offrir une expérience publique rapide et cohérente
- d’éviter les jointures cross-tenant à la volée
- de garder une architecture extensible pour tous les modules du projet

---

## 23. Conclusion

Le cœur technique du projet `Ticket` n’est pas seulement le multi-tenant.

C’est la capacité à faire coexister proprement :

- des opérations locales indépendantes par organisation
- un portail public unique et performant
- des modules métier très différents
- une gouvernance plateforme solide

L’architecture des données doit donc être pensée comme un système à responsabilités explicites :

- le tenant exécute
- le central orchestre
- les projections exposent
- les journaux sécurisent
- les agrégats pilotent

C’est cette discipline qui rendra la plateforme stable, évolutive et exploitable à long terme.
