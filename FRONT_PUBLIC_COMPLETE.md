# Documentation Complète du Front Public du Projet Ticket

## 1. Objet du document

Ce document décrit le **front public** du projet `Ticket`.

Le front public correspond à l’interface visible par tous les visiteurs, avant connexion ou pendant le parcours d’achat.

Il couvre :

- la découverte des contenus
- la recherche et la navigation
- les pages détail des modules
- le checkout
- l’onboarding vers l’espace utilisateur
- la communication publique de la plateforme et des tenants

Le front public doit être réalisé en **Next.js**.

---

## 2. Rôle du front public

Le front public est la vitrine commerciale et transactionnelle du projet.

Il doit permettre de :

- découvrir des événements et autres contenus
- filtrer par catégorie, lieu, date et prix
- consulter les pages détail
- acheter ou réserver
- contribuer ou candidater selon le module
- créer un compte utilisateur si nécessaire

---

## 3. Objectifs du front public

Le front public doit maximiser :

- la découverte
- la conversion
- la confiance
- la clarté du parcours d’achat
- la performance mobile
- le référencement naturel

---

## 4. Principes UX du front public

Le front public doit être conçu comme un produit B2C moderne.

## Attendus UX principaux

- mobile-first
- chargement rapide
- filtres visibles
- CTA clairs
- design visuel fort
- cohérence entre listing, détail et checkout
- réassurance paiement
- friction minimale jusqu’au paiement

---

## 5. Navigation principale du front public

## 5.1 Accueil

- hero principal
- mise en avant des contenus populaires
- catégories principales
- sélections éditoriales
- CTA vers les modules

## 5.2 Catalogue / listing

- événements
- stands
- formations
- appels à projets
- crowdfunding

## 5.3 Recherche globale

- recherche texte
- suggestions
- résultats filtrés

## 5.4 Détail contenu

- page détail événement ou module
- description
- organisateur
- offres / tickets
- CTA achat / contribution / candidature

## 5.5 Checkout

- panier
- informations acheteur
- paiement
- confirmation

## 5.6 Espace compte

- redirection vers le panel user

---

## 6. Pages publiques essentielles

## 6.1 Page d’accueil plateforme

La homepage publique doit contenir :

- proposition de valeur
- moteur de recherche principal
- catégories visibles
- top événements / modules tendances
- modules mis en avant
- blocs de réassurance
- CTA organisateur et CTA utilisateur

## 6.2 Listings par module

Chaque listing doit proposer :

- grille de cartes
- image de couverture
- titre
- catégorie
- date
- lieu
- prix d’appel
- statut / badge
- organisateur
- CTA principal

## Filtres recommandés

- catégorie
- ville
- pays
- date
- prix
- gratuit / payant
- populaire / récent
- module

## 6.3 Page détail événement

Une page événement doit afficher au minimum :

- cover
- titre
- catégorie
- organisateur
- date et heure
- lieu
- adresse
- prix à partir de
- description
- partage social
- tickets disponibles
- récapitulatif commande
- CTA acheter

## 6.4 Page détail formation

- titre
- catégorie
- intervenants
- dates
- lieu ou online
- programme
- prix
- capacité
- CTA inscription

## 6.5 Page détail stand

- nom du stand / offre
- catégorie
- descriptif
- prix
- quota disponible
- CTA réservation

## 6.6 Page détail appel à projets

- titre
- catégorie
- description
- conditions
- calendrier
- pièces à fournir
- frais éventuels
- CTA candidater

## 6.7 Page détail crowdfunding

- titre
- catégorie
- porteur de campagne
- objectif
- progression
- montant collecté
- paliers / contributions
- contreparties
- CTA contribuer

---

## 7. Cartes de listing publiques

Les cartes visibles sur le front public doivent idéalement contenir :

- image
- badge catégorie
- titre
- date ou échéance
- ville / pays
- prix d’appel
- organisateur
- bouton d’action
- indicateur populaire ou promotion si pertinent

---

## 8. Checkout et conversion

## 8.1 Parcours cible

Le parcours de conversion doit être :

- rapide
- rassurant
- lisible
- compatible mobile

## 8.2 Étapes recommandées

- sélection de l’offre
- panier ou récapitulatif
- informations acheteur
- connexion ou création compte si nécessaire
- paiement
- confirmation
- accès au panel user

## 8.3 Éléments à afficher au checkout

- détail de l’offre
- quantité
- sous-total
- frais
- total
- devise
- mode de paiement
- conditions de remboursement
- messages de sécurité

---

## 9. Contenus organisateur visibles publiquement

Le front public doit aussi permettre de valoriser l’organisateur.

## Éléments recommandés

- page profil organisateur
- avatar / logo
- bannière
- description
- réseaux sociaux
- autres contenus publiés
- bouton suivre

---

## 10. Référencement, partage et croissance

Le front public doit intégrer :

- SEO par page détail
- Open Graph
- Twitter cards
- URLs propres par slug
- partage WhatsApp, Facebook, X, LinkedIn, Telegram
- pages indexables par moteur de recherche

---

## 11. APIs attendues pour le front public

Le front public devra consommer des API pour :

- catalogue public
- recherche
- détail des contenus
- pages organisateur
- catégories publiques
- tickets / offres disponibles
- panier / checkout
- paiement
- création de compte et connexion

---

## 12. Données impliquées

Le front public s’appuie sur des projections publiques des données tenant.

## Données principales

- contenus publiés par module
- catégories
- tags si retenus
- organisateurs publics
- offres vendables publiées
- disponibilité
- prix et devises
- statuts publics

---

## 13. Pages additionnelles recommandées

Le front public peut aussi inclure :

- page catégories
- page villes / destinations
- page promotions
- page support / FAQ
- page contact
- page conditions générales
- page politique de confidentialité
- page devenir organisateur

---

## 14. Composants front à prévoir

Les composants clés à standardiser sont :

- header
- footer
- barre de recherche
- filtres catalogue
- carte contenu
- badge catégorie
- bloc organisateur
- bloc prix
- bloc tickets / offres
- résumé commande sticky
- modales auth
- toasts et feedback utilisateur

---

## 15. Résultat attendu

À la fin de l’implémentation du front public :

- la plateforme présente clairement tous les contenus disponibles
- les visiteurs peuvent découvrir, filtrer et acheter facilement
- chaque module dispose d’une page détail adaptée à son métier
- le checkout est cohérent et rassurant
- le front public et le panel user partagent une expérience homogène en Next.js
