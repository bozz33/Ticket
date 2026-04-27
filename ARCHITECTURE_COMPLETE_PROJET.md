# Architecture Complète du Projet Ticket

## 1. Objet du document

Ce document définit l’architecture fonctionnelle et technique complète de la plateforme `Ticket`, une plateforme numérique multi-organisation dédiée à la gestion d’événements, de formations, d’appels à projets, de stands d’exposition et de campagnes de financement participatif.

L’objectif n’est pas de décrire un MVP, mais une cible complète, cohérente et évolutive, qui pourra être mise en œuvre par phases sans remettre en cause les fondations du système.

Les choix d’intégration détaillés relatifs à `Paystack` sont formalisés dans `INTEGRATION_PAYSTACK_COMPLETE.md`.

Le plan de réalisation détaillé est formalisé dans `PLAN_DE_DEVELOPPEMENT_MODULE_PAR_MODULE.md`.

---

## 2. Vision produit

La plateforme doit permettre à plusieurs organisations de gérer de façon autonome leurs activités, tout en profitant d’un socle centralisé commun.

Les organisations cibles peuvent être :

- ONG
- entreprises
- promoteurs culturels
- centres de formation
- organisateurs de salons
- institutions lançant des appels à projets
- structures portant des campagnes de crowdfunding

La plateforme doit offrir :

- une expérience publique unifiée et moderne
- une gestion autonome par organisation
- une isolation forte des données métier
- un catalogue public global de toutes les activités publiées
- un design premium inspiré de standards comme `Boleto`
- des parcours proches des usages constatés sur des plateformes comme `Tikerama`
- une architecture extensible pour supporter plusieurs modules métier sans refonte structurelle

---

## 3. Décision d’architecture retenue

### 3.1 Choix principal

Le projet retient une architecture **multi-tenant côté métier**, avec un **frontend public unifié**.

Cela signifie :

- chaque organisation est un tenant
- chaque tenant dispose de ses propres données opérationnelles
- le portail public est unique pour tous les tenants
- les événements et pages organisateur sont accessibles via des URLs en chemin
- la plateforme ne dépend pas d’une prolifération de sous-domaines pour fonctionner

### 3.2 Conséquences directes

- le multi-tenant est utilisé pour l’isolation, la gouvernance et l’évolutivité
- le public consulte un seul portail commun
- chaque événement a un lien direct partageable
- chaque tenant possède une page publique dédiée de type organisateur
- les sous-domaines deviennent une option future, et non une obligation de départ

### 3.3 Exemple de structure d’URLs

- `/`
- `/evenements`
- `/evenements/{event-slug}`
- `/organisateurs/{tenant-slug}`
- `/formations`
- `/formations/{formation-slug}`
- `/appels-a-projets`
- `/appels-a-projets/{call-slug}`
- `/crowdfunding`
- `/crowdfunding/{campaign-slug}`
- `/stands`
- `/stands/{salon-slug}`

---

## 4. Architecture technique cible

## 4.1 Stack recommandée

### Backend

- Laravel
- Tenancy for Laravel v3
- PHP 8.x
- API REST principale
- Jobs/queues pour synchronisation, notifications, paiements et projections publiques

### Administration

- Filament PHP
- Filament 5
- 2 panels Filament distincts :
  - panel plateforme pour le super-admin et l’équipe plateforme
  - panel tenant pour chaque organisation
- spatie/laravel-permission pour les rôles et permissions
- Filament Shield pour l’intégration des permissions dans les panels

### Frontend public

- Next.js recommandé
- rendu hybride SSR/ISR/CSR selon les pages
- design system réutilisable
- UI inspirée des codes visuels premium de `Boleto`

### Paiements

- Paystack comme passerelle principale
- webhook + vérification serveur obligatoires pour confirmer les transactions

### Stockage et services transverses

- MySQL ou MariaDB pour les données applicatives
- Redis pour cache, sessions, files de messages légères
- stockage objet ou disque pour médias, billets, pièces jointes
- moteur de recherche optionnel à moyen terme pour recherche full-text

### Observabilité et exploitation

- logs applicatifs centralisés
- audit des actions sensibles
- monitoring des jobs, paiements, scans et erreurs critiques

---

## 4.2 Modèle de données global

Le système est organisé autour de deux niveaux de données.

### Niveau 1 : base centrale de plateforme

La base centrale, dite `landlord`, contient les données communes à la plateforme.

Elle gère notamment :

- tenants
- identités publiques des organisations
- domaines éventuels
- slugs publics
- index public des ressources publiées
- utilisateurs internes de la plateforme centrale
- règles globales
- commissions et configuration globale
- catalogue transversal pour le portail public
- logs globaux et supervision

### Niveau 2 : base dédiée par tenant

Chaque tenant possède sa base dédiée pour ses opérations métier.

Elle contient :

- événements
- billets
- commandes
- participants
- scans QR
- campagnes crowdfunding du tenant
- appels à projets du tenant
- stands du tenant
- formations du tenant
- équipes et permissions locales
- pièces jointes métier
- données financières du tenant

### Règle fondamentale

La base centrale ne remplace pas les bases tenants.

Elle stocke principalement :

- les métadonnées de plateforme
- les projections publiques nécessaires au portail global
- les éléments nécessaires au routage, à la recherche, à l’indexation et à la supervision

---

## 5. Grands domaines fonctionnels

Le projet complet est structuré en domaines fonctionnels indépendants mais cohérents.

- gestion de plateforme
- gestion des tenants
- identité publique des tenants
- billetterie
- stands
- appels à projets
- formations
- crowdfunding
- paiements et reversements
- QR code et contrôle d’accès
- notifications
- support et service client
- reporting et analytique
- modération et conformité
- contenus et médias
- API et intégrations

---

# 6. Module 1 : Gestion de la plateforme centrale

## 6.1 Objectif

Ce module permet à l’équipe plateforme d’administrer l’écosystème global.

## 6.2 Responsabilités

- création et activation des tenants
- suspension ou clôture d’un tenant
- configuration globale de la plateforme
- gestion des commissions
- gestion des catégories globales
- modération des contenus publics
- supervision des publications publiques
- administration des moyens de paiement globaux
- administration des règles de conformité
- support niveau plateforme

## 6.3 Entités principales

- tenant
- plan/abonnement
- commission model
- global category
- public publication index
- moderation case
- payment gateway config
- platform admin user

## 6.4 Rôles principaux

- super administrateur plateforme
- administrateur opérationnel
- support plateforme
- finance plateforme
- modérateur contenu

---

# 7. Module 2 : Gestion des tenants / organisations

## 7.1 Objectif

Ce module gère la vie d’une organisation dans la plateforme.

## 7.2 Données tenant

- raison sociale
- nom commercial
- logo
- bannière
- description
- contacts
- ville, pays, adresse
- réseaux sociaux
- statut de vérification
- politique de remboursement
- moyens de contact support
- paramètres de marque

## 7.3 Fonctionnalités

- onboarding d’un tenant
- configuration du profil public
- configuration des équipes
- création et gestion des utilisateurs du tenant
- affectation des permissions et visibilité des ressources selon les rôles
- configuration des rôles locaux
- configuration des moyens de paiement accessibles
- paramétrage des notifications
- définition des préférences de publication
- gestion des documents administratifs

## 7.4 Rôles locaux

- propriétaire tenant
- administrateur tenant
- manager événementiel
- responsable formation
- responsable appels à projets
- responsable crowdfunding
- agent de contrôle / scanner
- caissier / finance
- support tenant
- lecteur / analyste

---

# 8. Module 3 : Portail public global

## 8.1 Objectif

Le portail public est le point d’entrée unique pour tous les utilisateurs externes.

## 8.2 Pages principales

- accueil global
- catalogue des événements
- catalogue des formations
- catalogue des appels à projets
- catalogue des campagnes de crowdfunding
- catalogue des salons avec stands
- page organisateur
- page détail de ressource
- pages de recherche et filtres
- pages légales
- espace utilisateur

## 8.3 Règles UX

- navigation simple et rapide
- moteur de recherche accessible depuis les entrées principales
- cartes visuelles premium
- CTA visibles
- parcours d’achat ou d’inscription courts
- responsive mobile first
- compatibilité paiements locaux
- chargement rapide et SEO fort

## 8.4 Inspirations visuelles

L’inspiration peut reprendre :

- la mise en scène premium d’un template comme `Boleto`
- la logique de catalogue et de fiches publiques d’une plateforme comme `Tikerama`

La plateforme doit toutefois avoir sa propre identité de marque et son propre design system.

---

# 9. Module 4 : Page publique tenant / organisateur

## 9.1 Objectif

Chaque tenant doit disposer d’une page publique dédiée dans le portail commun.

## 9.2 Exemple d’URL

- `/organisateurs/{tenant-slug}`

## 9.3 Contenu de la page

- logo
- bannière
- description de l’organisation
- informations de contact
- réseaux sociaux
- localisation
- statut vérifié ou non
- événements à venir
- formations à venir
- appels à projets ouverts
- campagnes crowdfunding actives
- salons ou stands concernés

## 9.4 Rôle métier

Cette page remplace, au lancement, la nécessité d’un site complet séparé par tenant.

Elle fournit :

- une vitrine publique
- un point de confiance
- un point d’entrée partageable
- une cohérence avec le portail global

---

# 10. Module 5 : Billetterie

## 10.1 Objectif

Le module de billetterie permet à un tenant de vendre des billets pour des événements physiques, hybrides ou en ligne.

## 10.2 Sous-domaines internes

- gestion des événements
- gestion des sessions/dates
- gestion des catégories de billets
- gestion des stocks et quotas
- commandes et paiements
- émission des billets
- QR code et validation
- politiques de remboursement
- coupons et promotions
- rapports de vente

## 10.3 Données clés

### Événement

- titre
- slug
- description courte
- description longue
- catégorie
- tags
- date unique ou multiples dates
- lieu physique ou en ligne
- coordonnées géographiques
- images et médias
- statut de publication
- visibilité publique/privée
- organisateur de référence
- conditions d’accès

### Billet

- type de billet
- tarif
- devise
- quantité
- quota par commande
- période de vente
- avantages inclus
- règles de remboursement
- statut

### Commande

- référence
- client
- lignes d’achat
- montant total
- frais de service
- mode de paiement
- statut de paiement
- statut d’émission
- historique

### Ticket émis

- numéro unique
- QR code
- bénéficiaire
- statut d’usage
- date de scan
- point d’entrée
- agent valideur

## 10.4 Capacités attendues

- billets gratuits et payants
- tarifs multiples
- quotas par type
- événements privés
- événements récurrents
- billets nominatifs
- génération d’e-ticket
- scan QR
- annulation ou invalidation de ticket
- reprogrammation d’événement
- report des billets selon politique
- codes promo
- bundles ou packs à moyen terme

## 10.5 Parcours utilisateur

- découverte de l’événement
- consultation des détails
- choix du billet
- identification éventuelle
- paiement
- émission de ticket
- réception notification
- contrôle à l’entrée

---

# 11. Module 6 : Gestion des stands

## 11.1 Objectif

Permettre la commercialisation et la gestion d’espaces d’exposition dans le cadre de salons et foires.

## 11.2 Cas d’usage

- réservation d’un stand standard
- achat d’un stand premium
- choix d’emplacement selon plan
- dépôt d’informations exposant
- paiement échelonné ou total
- suivi de validation organisateur

## 11.3 Entités clés

- salon
- hall
- zone
- stand
- type de stand
- réservation
- exposant
- contrat
- pièce jointe exposant

## 11.4 Fonctionnalités

- catalogue des stands disponibles
- plan d’occupation
- blocage temporaire d’un stand pendant le checkout
- validation manuelle si nécessaire
- signature ou acceptation de conditions
- facturation liée au stand
- suivi des paiements
- génération de badges exposants à terme

---

# 12. Module 7 : Appels à projets

## 12.1 Objectif

Permettre à un tenant de publier des appels à candidatures, concours ou sélections officielles.

## 12.2 Types de cas couverts

- appel à projets ONG
- concours startup
- sélection artistique ou culturelle
- programme d’accélération
- bourses ou subventions

## 12.3 Entités principales

- appel à projets
- lot ou catégorie
- formulaire de candidature
- candidature
- pièce jointe
- évaluateur
- grille d’évaluation
- score
- décision
- notification

## 12.4 Fonctionnalités

- création d’un appel
- définition des critères d’éligibilité
- paramétrage des dates d’ouverture et de clôture
- soumission de dossier en ligne
- upload de pièces jointes
- gestion des brouillons
- validation de complétude
- workflow de revue
- notation des candidatures
- shortlisting
- publication des résultats

## 12.5 Particularités

- forte gestion documentaire
- workflow d’évaluation potentiellement complexe
- nécessité d’audit sur les décisions
- confidentialité forte sur les dossiers

---

# 13. Module 8 : Formations

## 13.1 Objectif

Permettre aux tenants de publier des formations, ateliers ou masterclass et de gérer les inscriptions.

## 13.2 Entités principales

- formation
- session
- intervenant
- lieu ou lien visio
- inscription
- liste d’attente
- attestation à terme
- support de formation

## 13.3 Fonctionnalités

- catalogue de formations
- sessions multiples
- nombre de places limité
- inscription gratuite ou payante
- règles d’admission manuelle ou automatique
- rappels avant session
- émargement / présence
- délivrance d’attestation à terme
- export listes participants

## 13.4 Points d’attention

- gestion des capacités
- sessions récurrentes
- prise en charge du distanciel
- suivi de présence

---

# 14. Module 9 : Crowdfunding

## 14.1 Objectif

Permettre aux ONG ou organisations de lever des fonds pour des projets spécifiques.

## 14.2 Entités clés

- campagne
- objectif financier
- palier
- contribution
- contributeur
- reçu
- contrepartie optionnelle
- actualité de campagne
- bénéficiaire

## 14.3 Fonctionnalités

- création de campagne
- publication avec objectif et échéance
- page publique détaillée
- collecte de dons
- suivi du montant collecté
- publication d’actualités
- présentation du projet financé
- reçus ou justificatifs selon règles locales
- reporting financier

## 14.4 Variantes possibles

- don simple
- don récurrent à terme
- campagne avec contreparties
- campagne liée à un événement ou un programme

## 14.5 Points d’attention

- conformité financière et réglementaire
- transparence sur l’usage des fonds
- historisation des contributions
- communication claire des statuts de paiement

---

# 15. Module 10 : Paiements, encaissement et reversements

## 15.1 Objectif

Ce module centralise les logiques de paiement et de distribution financière.

## 15.2 Moyens de paiement cibles

- Paystack comme moyen de paiement principal
- Mobile Money local
- carte bancaire
- paiement en point de vente physique à terme
- virement selon cas spécifiques

## 15.3 Fonctionnalités attendues

- initialisation de paiement
- confirmation asynchrone
- webhook sécurisé et rejouable
- vérification serveur de transaction
- gestion des échecs et expirations
- rapprochement de paiement
- émission de reçus
- ventilation plateforme / tenant
- reversement aux organisateurs
- journal financier
- remboursement total ou partiel selon règles

## 15.4 Règles d’architecture

- la logique de paiement ne doit pas être dispersée dans chaque module
- chaque module consomme un service de paiement commun
- les écritures financières doivent être historisées
- les webhooks ou callbacks externes doivent être sécurisés et rejouables

## 15.5 Notions de ledger interne

La plateforme doit prévoir un journal interne traçant :

- montant brut
- frais plateforme
- frais passerelle
- montant net tenant
- statut de reversement
- statut de remboursement

---

# 16. Module 11 : QR code, contrôle d’accès et opérations terrain

## 16.1 Objectif

Permettre la validation opérationnelle des tickets et droits d’accès.

## 16.2 Fonctionnalités

- génération de QR code unique
- scan via interface dédiée
- validation instantanée
- détection des doubles passages
- journal des scans
- affectation à un point d’entrée
- supervision des entrées en temps réel

## 16.3 Utilisateurs concernés

- contrôleur d’accès
- superviseur terrain
- organisateur

## 16.4 Cas avancés

- scan multi-portes
- scan partiellement offline à terme
- invalidation manuelle de billet
- accès différencié selon type de billet

---

# 17. Module 12 : Notifications et communication

## 17.1 Objectif

Gérer l’ensemble des communications transactionnelles et marketing autorisées.

## 17.2 Canaux

- email
- SMS à terme
- WhatsApp à terme si intégration disponible
- notifications internes

## 17.3 Cas d’usage

- confirmation de commande
- émission de ticket
- rappel d’événement
- changement d’horaire
- annulation
- relance paiement abandonné
- confirmation inscription formation
- dépôt candidature reçu
- changement statut candidature
- reçu contribution crowdfunding

## 17.4 Règles

- gabarits centralisés
- personnalisation légère par tenant
- journal des envois
- gestion des préférences utilisateur

---

# 18. Module 13 : Support client et service après-vente

## 18.1 Objectif

Assurer le traitement des demandes avant et après achat ou inscription.

## 18.2 Cas couverts

- ticket non reçu
- paiement débité sans confirmation
- demande de remboursement
- transfert ou correction d’informations
- annulation par organisateur
- assistance accès le jour J
- réclamation candidature ou contribution

## 18.3 Composants attendus

- centre d’aide
- FAQ publique
- formulaires de contact
- suivi des demandes support
- journal des décisions SAV

---

# 19. Module 14 : Reporting, analytics et pilotage

## 19.1 Objectif

Fournir aux tenants et à la plateforme les indicateurs de suivi métier, commercial et financier.

## 19.2 Indicateurs tenant

- ventes par événement
- taux de remplissage
- billets par catégorie
- panier moyen
- scans réalisés
- revenus nets
- performance des coupons
- provenance des commandes si traçage marketing
- progression des campagnes crowdfunding
- nombre de candidatures par appel
- taux de conversion formation

## 19.3 Indicateurs plateforme

- nombre de tenants actifs
- volume global transactions
- commissions
- incidents paiement
- qualité publication
- performance du catalogue public
- taux de transformation global

## 19.4 Exports

- CSV
- Excel
- PDF à terme pour certains états

---

# 20. Module 15 : Modération, conformité et audit

## 20.1 Objectif

Garantir la qualité, la légalité et la traçabilité de la plateforme.

## 20.2 Besoins

- audit des actions sensibles
- validation ou modération de publications selon politique
- gestion des contenus interdits ou frauduleux
- vérification des tenants
- conservation des traces de décision
- journal financier exploitable
- politique de confidentialité et consentements

## 20.3 Données sensibles

- pièces justificatives tenant
- données clients
- transactions financières
- pièces jointes candidatures

## 20.4 Mesures attendues

- rôles et permissions stricts
- historisation des actions critiques
- chiffrement des données sensibles si nécessaire
- politique de rétention des documents

---

# 21. Module 16 : CMS léger, médias et contenus éditoriaux

## 21.1 Objectif

Permettre à la plateforme et aux tenants de gérer certains contenus non purement transactionnels.

## 21.2 Contenus possibles

- bannières homepage
- pages statiques
- FAQ
- actualités
- contenu marketing des campagnes
- conditions et mentions par tenant

## 21.3 Médias

- images de couverture
- logos
- pièces jointes PDF
- supports de candidature
- documents de formation

---

# 22. Module 17 : API et intégrations

## 22.1 Objectif

Permettre l’ouverture progressive de la plateforme vers des services tiers.

## 22.2 Intégrations prévues

- passerelles de paiement
- solutions SMS
- email provider
- outils analytics
- CRM ou ERP à terme
- lecteurs/scanners mobiles à terme

## 22.3 Exposition API

L’API devra être pensée pour :

- le frontend public
- les backoffices
- des intégrations tierces futures
- des applications mobiles éventuelles

---

# 23. Routing public recommandé

## 23.1 Principes

Le frontend public doit être unique et centralisé.

Les URLs publiques doivent être simples, lisibles et stables.

## 23.2 Structure recommandée

- `/`
- `/evenements`
- `/evenements/{slug}`
- `/organisateurs/{slug}`
- `/formations`
- `/formations/{slug}`
- `/appels-a-projets`
- `/appels-a-projets/{slug}`
- `/crowdfunding`
- `/crowdfunding/{slug}`
- `/stands`
- `/stands/{slug}`
- `/compte`
- `/mes-commandes`
- `/mes-inscriptions`
- `/mes-contributions`

## 23.3 Règle d’unicité

Les slugs publics doivent être uniques par type de ressource, avec fallback sur UUID si collision.

---

# 24. Publication publique et projection des données

## 24.1 Problème à résoudre

La plateforme veut :

- une base dédiée par tenant
- un catalogue public global
- des pages publiques rapides

## 24.2 Réponse architecturale

Chaque ressource publiée dans un tenant doit alimenter une **projection publique centrale**.

## 24.3 Fonctionnement cible

1. le tenant crée ou modifie une ressource métier
2. un événement applicatif est déclenché
3. un job met à jour l’index public central
4. le portail public lit cet index pour les listings et les résumés
5. le détail complet peut être servi à partir de la projection ou d’un agrégat enrichi selon la stratégie retenue

## 24.4 Ressources projetées

- événements
- pages organisateur
- formations publiées
- appels à projets ouverts
- campagnes crowdfunding actives
- salons et stands publics

---

# 25. Sécurité et gestion des accès

## 25.1 Principes

- séparation stricte des rôles
- séparation tenant / plateforme
- vérification systématique du contexte tenant
- audit des actions sensibles
- sécurisation des callbacks de paiement
- limitation des accès publics aux seules données publiées

## 25.2 Catégories d’utilisateurs

- visiteur public
- client final
- contributeur
- candidat
- participant formation
- agent tenant
- administrateur tenant
- administrateur plateforme

## 25.3 Sujets critiques

- accès aux données inter-tenant
- falsification de scan ticket
- double validation QR
- usurpation sur les reversements
- exposition de documents confidentiels

---

# 26. Performances et scalabilité

## 26.1 Exigences

La plateforme doit rester fluide malgré :

- plusieurs tenants
- plusieurs événements simultanés
- pics de trafic pendant les ventes
- scans intensifs le jour des événements

## 26.2 Réponses techniques

- projection publique optimisée
- cache sur listings publics
- files de jobs pour traitements asynchrones
- séparation claire lecture publique / opérations métier
- pagination systématique
- indexation des champs de recherche critiques

---

# 27. SEO, partage et visibilité

## 27.1 Besoins

Les ressources publiques doivent être facilement découvrables et partageables.

## 27.2 Bonnes pratiques structurelles

- slug lisible
- métadonnées SEO par ressource
- images sociales
- pages publiques SSR/ISR
- données structurées à terme sur événements

---

# 28. Design system et UX cible

## 28.1 Objectif

Créer une identité haut de gamme, réutilisable et cohérente à travers tous les modules.

## 28.2 Inspirations

- esthétique premium et immersive d’un template type `Boleto`
- simplicité de parcours de plateformes comme `Tikerama`

## 28.3 Principes UX

- peu d’étapes pour convertir
- forte lisibilité des dates, lieux et prix
- accès rapide au bouton principal
- cartes et sections très visuelles
- cohérence entre modules
- mobile first
- accessibilité raisonnable

## 28.4 Personnalisation tenant

La personnalisation par tenant doit rester contrôlée :

- logo
- bannière
- couleurs secondaires limitées
- contenu public spécifique

La plateforme garde un cadre global pour préserver cohérence et maintenabilité.

---

# 29. Backoffices attendus

## 29.1 Backoffice plateforme

Fonctions :

- gestion tenants
- supervision
- modération
- finances globales
- configuration globale
- support niveau 2/3

## 29.2 Backoffice tenant

Fonctions :

- gestion profil organisateur
- événements
- billets
- commandes
- scans
- stands
- formations
- appels à projets
- crowdfunding
- reporting local
- utilisateurs et rôles du tenant

---

# 30. Modules transverses à prévoir dès l’architecture

Même si certains seront développés plus tard, ils doivent être anticipés dans la structure.

- système de fichiers et médias
- journal d’audit
- notifications
- moteur de recherche
- coupons / promotions
- finance et reversements
- rôles et permissions
- centre d’aide
- analytics
- conformité documentaire

---

# 31. Risques principaux du projet

## 31.1 Risques techniques

- complexité inter-tenant sur le catalogue public
- incohérences entre base tenant et projection publique
- complexité des paiements locaux
- charge opérationnelle du scan terrain
- surcharge fonctionnelle si tous les modules sont traités sans priorisation

## 31.2 Risques produit

- vouloir trop personnaliser les tenants trop tôt
- confusion entre marketplace globale et mini-sites individuels
- modules trop hétérogènes sans noyau commun clair

## 31.3 Risques organisationnels

- absence de règles de validation de contenu
- manque de politique de remboursement et reversement
- manque de gouvernance sur les rôles et responsabilités

---

# 32. Ordre logique de construction du projet complet

Même si la cible est complète, la réalisation doit suivre un ordre rationnel.

## 32.1 Fondations

- multi-tenant
- identité tenant
- portail public
- projection publique
- authentification
- rôles et permissions
- paiements de base
- notifications

## 32.2 Socle métier cœur

- billetterie
- page organisateur
- QR code et contrôle
- reporting de base

## 32.3 Extensions métier

- formations
- stands
- appels à projets
- crowdfunding

## 32.4 Gouvernance et optimisation

- modération avancée
- reversements avancés
- analytics avancées
- moteur de recherche enrichi
- intégrations externes

---

# 33. Recommandation finale

## 33.1 Choix de structure

La structure la plus cohérente pour `Ticket` est la suivante :

- backend Laravel multi-tenant
- Tenancy for Laravel v3 en mode multi-base de données
- base centrale de plateforme + base dédiée par tenant
- Filament pour les backoffices
- Filament 5 avec 2 panels : plateforme et tenant
- spatie/laravel-permission + Filament Shield pour le RBAC
- Paystack pour les paiements
- Next.js pour le frontend public unifié
- URLs publiques en chemin, sans dépendre de sous-domaines multiples
- projection publique centrale pour exposer tous les contenus publiés

## 33.2 Positionnement produit final

`Ticket` doit être conçu comme :

- une plateforme événementielle multi-organisation
- avec portail public unique
- avec pages organisateur dédiées
- avec modules spécialisés mais cohérents
- avec isolation forte des opérations métier
- avec une expérience utilisateur premium et moderne

---

# 34. Conclusion

Le projet ne doit pas être pensé comme une juxtaposition de petits sites indépendants.

Il doit être pensé comme un **écosystème unifié**, dans lequel :

- la plateforme orchestre l’ensemble
- chaque tenant conserve son autonomie métier
- le public bénéficie d’une expérience simple, centralisée et fluide
- chaque module métier repose sur des fondations communes robustes

Cette architecture permet de soutenir durablement :

- la billetterie
- les stands
- les formations
- les appels à projets
- le crowdfunding

sans remettre en cause la structure du système à chaque extension.
