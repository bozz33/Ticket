# Documentation Complète du Panel Tenant du Projet Ticket

## 1. Objet du document

Ce document décrit de manière complète le **panel tenant** du projet `Ticket`.

Il précise :

- les objectifs du panel tenant
- les profils utilisateurs concernés
- les sections de navigation
- les ressources métier à exposer
- les éléments fonctionnels attendus par module
- les dépendances de données et de services
- les principes UX, sécurité et permissions

Ce document complète les documents suivants :

- `ARCHITECTURE_COMPLETE_PROJET.md`
- `ARCHITECTURE_DONNEES_COMPLETE.md`
- `MODULES_TABLES_RELATIONS_COMPLETE.md`
- `PLAN_DE_DEVELOPPEMENT_MODULE_PAR_MODULE.md`

---

## 2. Rôle du panel tenant

Le panel tenant est le **back-office de l’organisateur**.

Il permet à chaque tenant de :

- gérer son organisation
- administrer son équipe
- configurer ses modules activés
- créer et gérer ses contenus vendables
- suivre ses ventes, transactions et reversements
- piloter ses opérations quotidiennes

Le panel tenant doit être réalisé avec **Filament**, car il s’agit d’un espace d’administration métier, orienté productivité et gestion interne.

---

## 3. Principes fonctionnels du panel tenant

## 3.1 Séparation des responsabilités

Le panel tenant ne doit pas gérer les référentiels centraux de gouvernance.

Exemples :

- les **catégories** sont gérées au niveau super-admin
- les plans d’abonnement sont gérés au niveau plateforme
- les politiques globales sont gérées au niveau plateforme

Le tenant consomme donc certains référentiels en lecture ou via synchronisation.

## 3.2 Visibilité conditionnelle

Le panel tenant doit afficher les ressources selon :

- les **permissions RBAC**
- les **features activées** pour le tenant
- l’**état de l’abonnement**

## 3.3 Productivité admin

Le panel tenant doit privilégier :

- formulaires rapides
- tableaux filtrables
- actions groupées
- statuts lisibles
- KPI utiles
- workflows simples

---

## 4. Utilisateurs du panel tenant

Les profils cibles sont :

- **Owner / Admin tenant**
- **Manager organisation**
- **Responsable billetterie**
- **Responsable finance**
- **Responsable marketing / communication**
- **Contrôle d’accès / check-in**
- **Support client tenant**

---

## 5. Navigation recommandée du panel tenant

## 5.1 Tableau de bord

- Dashboard tenant
- KPI d’exploitation
- alertes rapides
- raccourcis opérationnels

## 5.2 Organisation

- Organisation / profil public
- contacts
- réseaux sociaux
- branding
- paramètres tenant

## 5.3 Modules

- Ticket / Événements
- Stands
- Formations
- Appels à projets
- Crowdfunding

## 5.4 Billetterie / ventes

- tickets / offres
- commandes
- participants / bénéficiaires
- check-in / scan
- coupons / promotions

## 5.5 Finance

- transactions
- reversements
- exports financiers
- rapports de ventes

## 5.6 Équipe et sécurité

- utilisateurs tenant
- rôles
- permissions
- invitations
- sessions / journal d’accès

## 5.7 Support et relation client

- demandes support
- messages / notifications
- FAQ tenant

---

## 6. Dashboard tenant

Le dashboard tenant doit afficher des KPI directement exploitables.

## KPI minimums retenus

- souscription active
- nombre de transactions
- demandes de reversement
- commission du mois
- solde net du mois

## KPI complémentaires recommandés

- chiffre d’affaires du mois
- commandes payées
- commandes en attente
- nombre de tickets émis
- nombre de check-ins
- meilleur module du mois
- meilleure offre vendue

## Blocs complémentaires recommandés

- dernières commandes
- prochains événements / activités
- campagnes proches de la fin
- actions rapides
- alertes de stock ticket faible
- alertes de reversement

---

## 7. Référentiel organisation

## 7.1 Profil organisation

Le tenant doit pouvoir gérer :

- nom d’affichage
- logo
- bannière
- description
- email support
- téléphone
- site web
- adresse
- ville
- pays
- liens sociaux
- politique de remboursement
- conditions d’utilisation tenant

## 7.2 Paramètres tenant

Le tenant doit pouvoir configurer :

- devise par défaut
- fuseau horaire
- langue principale
- branding visuel
- templates email
- paramètres d’affichage public
- options de modération

---

## 8. Gestion des modules métier

## 8.1 Règle commune à tous les modules

Chaque module doit pouvoir :

- appartenir à une **catégorie synchronisée** depuis le central
- avoir un **statut public**
- avoir un **slug public**
- être activé / désactivé
- être publié ou non
- posséder une ou plusieurs **offres vendables**

## 8.2 Catégories

Le tenant **ne doit pas avoir de resource `Catégories` dans sa navigation**.

Les catégories sont :

- créées au niveau super-admin
- synchronisées vers le tenant
- consommées dans les formulaires des modules

---

## 9. Module Ticket / Événements

## 9.1 Éléments fonctionnels

Un événement doit contenir au minimum :

- catégorie
- titre
- slug
- résumé
- description
- statut public
- image de couverture
- date(s)
- ville / pays
- lieu
- adresse
- devise
- publication
- activation

## 9.2 Sous-éléments attendus

- plusieurs dates si nécessaire
- galerie média
- FAQ
- organisateur affiché
- conditions de participation
- options de partage

## 9.3 Gestion commerciale

Chaque événement doit pouvoir avoir :

- un ou plusieurs tickets
- billets gratuits ou payants
- paliers tarifaires
- stock par ticket
- dates d’ouverture / fermeture de vente
- minimum / maximum par commande
- coupons / promotions

---

## 10. Module Stands

## 10.1 Éléments fonctionnels

Un stand doit contenir au minimum :

- catégorie
- nom
- slug
- résumé
- description
- statut public
- prix de base
- quantité disponible
- devise
- publication
- activation

## 10.2 Cas d’usage

- réservation de stand sur salon
- vente d’emplacement exposant
- location d’espace commercial temporaire

## 10.3 Gestion commerciale

Chaque stand peut proposer :

- plusieurs formats de stand
- plusieurs niveaux tarifaires
- options additionnelles
- quotas disponibles

---

## 11. Module Formations

## 11.1 Éléments fonctionnels

Une formation doit contenir au minimum :

- catégorie
- titre
- slug
- résumé
- description
- dates de début et fin
- lieu ou mode en ligne
- devise
- statut public
- publication
- activation

## 11.2 Gestion commerciale

Une formation peut avoir :

- billet standard
- billet premium
- accès groupe
- frais d’inscription
- capacité maximale
- liste d’attente

## 11.3 Données complémentaires recommandées

- intervenants
- programme
- prérequis
- certificat
- documents téléchargeables

---

## 12. Module Appels à projets

## 12.1 Éléments fonctionnels

Un appel à projets doit contenir :

- catégorie
- titre
- slug
- résumé
- description
- fenêtre d’ouverture des candidatures
- date limite
- statut public
- publication
- activation

## 12.2 Données complémentaires recommandées

- conditions d’éligibilité
- pièces à fournir
- étapes de sélection
- jury ou comité
- résultats / statuts

## 12.3 Logique commerciale ou administrative

Selon le cas, un appel à projets peut avoir :

- dépôt gratuit
- frais de dossier
- ticket de participation
- plusieurs types de candidatures

---

## 13. Module Crowdfunding

## 13.1 Éléments fonctionnels

Une campagne crowdfunding doit contenir :

- catégorie
- titre
- slug
- résumé
- description
- objectif financier
- montant collecté
- devise
- période de campagne
- statut public
- publication
- activation

## 13.2 Paliers / contributions

Chaque campagne peut avoir :

- contribution libre
- paliers de contribution
- contreparties
- quantité limitée par palier
- fenêtre de disponibilité par palier

## 13.3 Indicateurs métier

- progression vers l’objectif
- nombre de contributeurs
- panier moyen
- taux de conversion visite / contribution

---

## 14. Tickets, offres et produits vendables

Le panel tenant doit inclure une gestion unifiée des éléments vendables.

## 14.1 Ressource recommandée

Une ressource commune doit permettre de gérer :

- tickets d’événement
- offres de stands
- inscriptions formation
- frais de dossier appels à projets
- paliers / contreparties crowdfunding

## 14.2 Champs principaux

- nom
- code
- description
- type d’offre
- prix
- devise
- stock total
- stock restant
- période de vente
- minimum / maximum par commande
- statut
- ordre d’affichage

---

## 15. Commandes, participants et contrôle

## 15.1 Commandes

Le panel tenant doit proposer :

- liste des commandes
- détail commande
- statut de paiement
- montant brut
- frais
- net
- canal de paiement
- export CSV / Excel

## 15.2 Participants / bénéficiaires

Le tenant doit pouvoir consulter :

- participants d’événements
- inscrits formations
- exposants / réservataires de stands
- candidats appels à projets
- contributeurs crowdfunding

## 15.3 Check-in / validation

Pour les modules concernés, le tenant doit disposer de :

- scan QR code
- saisie manuelle code
- historique de check-in
- rejet / duplication / contrôle statut

---

## 16. Finance dans le panel tenant

## 16.1 Ressources financières essentielles

- transactions
- reversements
- exports financiers
- rapports

## 16.2 Indicateurs financiers

- total encaissé
- total net
- commissions
- reversements en attente
- reversements payés
- volume par module
- évolution mensuelle

## 16.3 Exports recommandés

- ventes par module
- ventes par offre
- reversements
- paiements échoués
- commandes remboursées

---

## 17. Équipe, rôles et permissions

Le panel tenant doit permettre :

- création d’utilisateurs
- invitation d’utilisateurs
- attribution de rôles
- réglage fin des permissions
- désactivation d’accès

## Rôles de base recommandés

- owner
- admin
- finance
- ticketing_manager
- marketing_manager
- operator
- support
- checkin_agent

---

## 18. Notifications et support

Le panel tenant doit prévoir :

- notifications système
- notifications paiement
- notifications reversement
- alertes de stock bas
- alertes d’expiration de campagne ou vente
- centre de support

---

## 19. APIs et services attendus

## 19.1 Services métier

- service de gestion des modules
- service de gestion des tickets / offres
- service de commande
- service de paiement
- service de reversement
- service de projection publique
- service de check-in

## 19.2 API à exposer

- CRUD modules tenant
- CRUD offres vendables
- lecture dashboard tenant
- commandes et détails
- scan / check-in
- exports
- notifications

---

## 20. Contraintes UX du panel tenant

Le panel tenant doit rester orienté back-office.

## Attendus UX

- navigation simple
- tableaux rapides
- badges de statut
- actions de masse
- filtres fréquents
- recherche globale
- formulaires découpés par sections
- feedback clair en cas d’erreur métier

---

## 21. Résultat attendu

À la fin de l’implémentation du panel tenant :

- un organisateur peut gérer son activité depuis un back-office unique
- les modules sont visibles selon les features activées
- les catégories restent gouvernées par le central
- chaque module peut publier une ou plusieurs offres vendables
- le tenant peut suivre ventes, paiements et reversements
- le panel est exploitable au quotidien par des équipes non techniques
