# Documentation Complète du Panel User du Projet Ticket

## 1. Objet du document

Ce document décrit le **panel user** du projet `Ticket`.

Le panel user correspond à l’espace personnel du **client final** : acheteur, participant, contributeur, candidat ou exposant selon le module utilisé.

Il précise :

- les objectifs de l’espace utilisateur
- les sections et écrans attendus
- les données à afficher
- les interactions possibles
- les règles d’authentification et de sécurité
- les principes UX recommandés

---

## 2. Rôle du panel user

Le panel user n’est pas un back-office d’administration.

C’est un **espace personnel orienté self-service**, destiné aux utilisateurs finaux.

Il doit permettre à l’utilisateur de :

- retrouver ses achats
- télécharger ses tickets
- suivre ses paiements
- gérer son profil
- voir ses inscriptions et contributions
- suivre ses favoris et organisateurs suivis
- accéder facilement à ses QR codes et confirmations

Le panel user doit être réalisé en **Next.js**, pour conserver une UX moderne, rapide et cohérente avec le front public.

---

## 3. Profils concernés

Le panel user peut être utilisé par :

- un acheteur de tickets événement
- un participant à une formation
- un exposant ayant réservé un stand
- un candidat à un appel à projets
- un contributeur à une campagne crowdfunding
- un utilisateur mixte ayant plusieurs usages

---

## 4. Objectifs fonctionnels du panel user

Le panel user doit permettre une expérience simple autour de trois piliers :

- **retrouver** ses opérations
- **gérer** ses informations
- **agir rapidement** en autonomie

---

## 5. Architecture fonctionnelle du panel user

## 5.1 Tableau de bord utilisateur

Le dashboard utilisateur doit synthétiser :

- commandes récentes
- tickets actifs
- prochaines activités
- contributions récentes
- candidatures en cours
- actions rapides

## 5.2 Mes tickets

L’utilisateur doit pouvoir :

- voir tous ses tickets
- filtrer par module
- afficher le QR code
- télécharger en PDF ou wallet pass si prévu
- consulter les détails de l’activité liée
- retrouver le statut du ticket

## 5.3 Mes commandes

L’utilisateur doit pouvoir :

- voir toutes ses commandes
- accéder au détail ligne par ligne
- voir le statut de paiement
- télécharger son reçu ou facture
- relancer un paiement si autorisé

## 5.4 Mes activités

Selon les modules, l’utilisateur peut voir :

- événements achetés
- formations réservées
- stands réservés
- appels à projets déposés
- campagnes soutenues

## 5.5 Mon profil

Le panel doit proposer :

- profil personnel
- email
- téléphone
- photo éventuelle
- préférences de langue
- préférences notifications
- sécurité du compte

## 5.6 Favoris et suivis

Le user doit pouvoir :

- enregistrer des événements ou contenus en favoris
- suivre des organisateurs
- retrouver ses contenus sauvegardés

---

## 6. Navigation recommandée du panel user

## 6.1 Accueil

- résumé du compte
- accès rapide aux tickets
- prochains contenus

## 6.2 Mes tickets

- liste tickets
- détail ticket
- QR code

## 6.3 Mes commandes

- historique commandes
- détail commande
- reçus

## 6.4 Mes inscriptions et participations

- formations
- stands
- appels à projets
- contributions crowdfunding

## 6.5 Mes favoris

- favoris contenus
- organisateurs suivis

## 6.6 Mon profil

- informations personnelles
- sécurité
- préférences
- notifications

---

## 7. Détail fonctionnel par section

## 7.1 Dashboard utilisateur

Le dashboard doit afficher :

- prochaine activité
- nombre de tickets actifs
- dernière commande
- statut des paiements récents
- contribution la plus récente
- message de bienvenue personnalisé

## 7.2 Mes tickets

Chaque ticket doit exposer :

- nom de l’activité
- type de ticket
- quantité
- date et heure
- lieu
- organisateur
- numéro ou code ticket
- QR code
- statut

## Actions utilisateur

- afficher
- télécharger
- partager si autorisé
- consulter le lieu
- consulter les conditions d’accès

## 7.3 Mes commandes

Chaque commande doit exposer :

- référence
- date
- montant total
- devise
- statut paiement
- moyen de paiement
- détail des lignes
- frais éventuels
- montant net débité

## Actions utilisateur

- consulter détail
- télécharger reçu
- retenter paiement si impayé
- contacter support si anomalie

## 7.4 Mes participations par module

### Événements

- billet acheté
- statut check-in
- informations pratiques

### Formations

- statut inscription
- session concernée
- documents éventuels
- certificat si disponible

### Stands

- stand réservé
- format choisi
- documents demandés
- instructions logistiques

### Appels à projets

- candidature déposée
- statut du dossier
- pièces transmises
- messages de suivi

### Crowdfunding

- campagne soutenue
- montant contribution
- palier choisi
- reçu de contribution

---

## 8. Authentification et sécurité

Le panel user doit proposer :

- inscription
- connexion email / mot de passe
- connexion sociale si retenue
- réinitialisation mot de passe
- vérification email
- gestion des sessions
- MFA en option

## Exigences de sécurité

- séparation stricte comptes admin / tenant / public
- protection contre les accès aux tickets d’autrui
- tokens sécurisés pour téléchargement et consultation
- audit des paiements et opérations sensibles

---

## 9. Notifications utilisateur

Le panel user doit centraliser :

- confirmation d’achat
- confirmation paiement
- émission ticket
- rappel avant événement
- mise à jour de candidature
- mise à jour campagne soutenue
- remboursement ou incident paiement

## Canaux recommandés

- email
- in-app
- WhatsApp ou SMS selon priorités business

---

## 10. APIs attendues pour le panel user

Le panel user devra consommer des API dédiées pour :

- authentification
- profil utilisateur
- commandes
- tickets
- paiements
- inscriptions
- contributions
- favoris
- notifications

---

## 11. Tables et données concernées

Les tables impliquées côté user incluent au minimum :

- `users` ou table public users dédiée selon architecture retenue
- `orders`
- `order_items`
- `payments`
- `tickets`
- `attendees` / `registrations`
- `favorites`
- `notifications`
- `user_sessions`

Selon les modules :

- `training_registrations`
- `stand_reservations`
- `call_submissions`
- `campaign_contributions`

---

## 12. Principes UX du panel user

Le panel user doit être très lisible et mobile-first.

## Attendus UX

- navigation courte
- design cohérent avec le front public
- bouton principal toujours clair
- historique facile à comprendre
- QR code accessible rapidement
- filtres simples
- support visible
- temps de chargement faibles

---

## 13. Parcours prioritaires à couvrir

Les parcours les plus importants sont :

- se connecter
- retrouver un ticket acheté
- afficher un QR code
- vérifier le paiement d’une commande
- télécharger un reçu
- consulter une inscription ou contribution
- modifier son profil

---

## 14. Résultat attendu

À la fin de l’implémentation du panel user :

- l’utilisateur final dispose d’un espace personnel unique
- il peut retrouver tous ses achats et participations
- il peut afficher rapidement ses tickets et QR codes
- il peut suivre ses paiements et reçus
- l’expérience reste simple, moderne et cohérente avec le front public
