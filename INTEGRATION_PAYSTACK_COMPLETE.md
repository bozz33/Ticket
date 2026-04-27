# Intégration Paystack Complète du Projet Ticket

## 1. Objet du document

Ce document formalise la stratégie d’intégration de `Paystack` dans le projet `Ticket`.

Il complète les documents suivants :

- `ARCHITECTURE_COMPLETE_PROJET.md`
- `ARCHITECTURE_DONNEES_COMPLETE.md`
- `MODULES_TABLES_RELATIONS_COMPLETE.md`
- `CONCEPTION_TECHNIQUE_DETAILLEE_COMPLETE.md`

Il décrit :

- le rôle de `Paystack` dans l’architecture
- les flux d’encaissement
- la confirmation de paiement
- la stratégie de reversement
- les tables impactées
- les jobs et services nécessaires
- les règles de sécurité et d’idempotence

---

## 2. Positionnement de Paystack dans Ticket

## 2.1 Rôle retenu

`Paystack` est la passerelle de paiement principale du projet.

Elle est utilisée pour :

- paiement de billets
- paiement d’inscriptions à des formations
- paiement de contributions de crowdfunding
- paiement de réservations de stands
- reversements vers les tenants lorsque le modèle financier le demande

## 2.2 Principe d’architecture

La logique `Paystack` ne doit pas être dispersée dans les modules métier.

Les modules métier consomment un socle commun :

- `PaymentService`
- `PaystackService`
- `PaystackWebhookService`
- `LedgerService`
- `PayoutService`

## 2.3 Stratégie recommandée au démarrage

La stratégie la plus simple et la plus robuste au démarrage est :

- la plateforme encaisse
- la transaction est vérifiée côté serveur
- les écritures financières sont générées
- le montant net dû au tenant est calculé
- le reversement est exécuté plus tard selon les règles métier

Autrement dit, la recommandation de départ est :

- **collecte centralisée puis reversement différé**

Cette approche simplifie :

- la réconciliation
- les remboursements
- la traçabilité
- la gestion des incidents
- la séparation plateforme / tenant

---

## 3. Principes de sécurité obligatoires

## 3.1 Règle de vérité paiement

Le retour navigateur ne constitue jamais à lui seul une preuve suffisante de paiement.

La confirmation fiable repose sur :

- le webhook `Paystack`
- la vérification serveur de la transaction
- la cohérence avec la référence interne attendue

## 3.2 Idempotence obligatoire

Les traitements suivants doivent être idempotents :

- réception de webhook
- vérification de transaction
- création d’écriture financière
- émission de billets
- mise à jour de commande
- création de reversement

## 3.3 Journalisation obligatoire

Chaque échange critique avec `Paystack` doit être traçable :

- tentative d’initialisation
- réponse d’initialisation
- webhook reçu
- résultat de vérification
- demande de reversement
- résultat du reversement
- incident de paiement

## 3.4 Gestion des secrets

Les secrets `Paystack` ne doivent jamais être exposés au frontend.

Ils doivent être stockés côté serveur uniquement via la configuration sécurisée de l’application.

---

## 4. Ressources métier concernées

## 4.1 Modules payants

Les flux `Paystack` doivent pouvoir être reliés à l’une des ressources sources suivantes :

- `order`
- `training_enrollment`
- `contribution`
- `stand_reservation`

## 4.2 Référence métier source

Toute transaction doit être corrélée à une ressource métier source via :

- `source_type`
- `source_id`
- `source_public_id` si nécessaire
- `tenant_id` ou `tenant_public_id`
- `reference` interne unique

---

## 5. Flux d’encaissement recommandé

## 5.1 Étape 1 : création de l’intention métier

Le module métier crée d’abord sa ressource locale :

- commande
- inscription
- contribution
- réservation de stand

Cette ressource passe ensuite dans un état compatible avec une attente de paiement.

## 5.2 Étape 2 : création de tentative de paiement

Le backend crée un enregistrement `payment_attempt`.

Il contient notamment :

- référence interne unique
- module source
- ressource source
- tenant concerné
- devise
- montant attendu
- canal
- statut initial

## 5.3 Étape 3 : initialisation chez Paystack

Le backend initialise la transaction auprès de `Paystack`.

La réponse attendue doit permettre de récupérer au minimum :

- la référence envoyée
- les métadonnées utiles au retour
- l’URL de paiement ou l’information d’autorisation nécessaire

## 5.4 Étape 4 : redirection ou poursuite du parcours utilisateur

Le frontend oriente l’utilisateur vers le parcours de paiement prévu.

À ce stade :

- la commande n’est pas encore considérée comme payée
- le billet n’est pas encore émis
- la contribution n’est pas encore confirmée

## 5.5 Étape 5 : confirmation asynchrone

Après paiement, le système reçoit :

- un webhook éventuel
- puis effectue ou rejoue une vérification serveur

La transaction n’est validée qu’après confirmation serveur.

## 5.6 Étape 6 : traitement métier après succès

En cas de succès confirmé :

- mise à jour de `payment_attempt`
- création de `payment_confirmation`
- création de `payment_transaction`
- création d’écritures de ledger tenant et plateforme
- mise à jour de la ressource métier source
- émission de billets ou activation métier si nécessaire
- déclenchement des notifications

## 5.7 Étape 7 : traitement des échecs

En cas d’échec ou d’abandon :

- mise à jour du statut de tentative
- mise à jour de la ressource source
- traçage de l’incident si nécessaire
- relance métier ou réessai utilisateur selon le cas

---

## 6. Confirmation de paiement

## 6.1 Règle cible

Un paiement est considéré comme confirmé uniquement lorsqu’un traitement serveur l’a validé.

## 6.2 Sources de confirmation

Les sources de confirmation sont :

- `webhook`
- `verify transaction`

## 6.3 Ordre de confiance recommandé

Ordre recommandé :

- webhook reçu et validé
- vérification serveur cohérente
- référence interne reconnue
- montant cohérent
- devise cohérente

## 6.4 Cas à détecter

Il faut traiter explicitement :

- paiement réussi mais commande non finalisée
- webhook reçu plusieurs fois
- retour navigateur sans webhook
- webhook reçu pour une référence inconnue
- montant reçu différent du montant attendu
- devise incohérente
- webhook tardif après expiration métier

---

## 7. Webhooks Paystack

## 7.1 Règles minimales

Chaque webhook doit être :

- authentifié
- journalisé
- idempotent
- corrélé à une ressource source
- rejouable en sécurité

## 7.2 Données à conserver

Le système doit conserver au minimum :

- type d’événement
- payload reçu
- en-têtes utiles
- date de réception
- résultat de validation
- référence interne corrélée
- résultat du traitement
- éventuelle erreur de traitement

## 7.3 Table recommandée

La table `gateway_webhook_logs` doit servir à :

- stocker la trace brute
- suivre les doublons
- historiser les erreurs
- permettre les reprises manuelles ou automatiques

---

## 8. Vérification serveur

## 8.1 Objectif

La vérification serveur confirme l’état final de la transaction côté passerelle.

## 8.2 Cas d’usage

Elle doit être utilisée pour :

- confirmer un paiement après retour utilisateur
- compléter un webhook partiel
- rejouer un traitement en cas de doute
- résoudre un incident de rapprochement

## 8.3 Résultat attendu

La vérification doit permettre de mettre à jour :

- le statut de la tentative
- le statut de la ressource métier
- la transaction financière finale
- la journalisation d’audit

---

## 9. Stratégie de reversement

## 9.1 Recommandation

Le modèle recommandé pour `Ticket` au démarrage est :

- encaissement plateforme
- calcul du net tenant
- reversement ultérieur vers le tenant

## 9.2 Pourquoi cette stratégie est recommandée

Elle simplifie :

- le multi-tenant
- la comptabilité interne
- la gestion des commissions
- le traitement des remboursements
- la résolution d’incidents
- la supervision par la plateforme

## 9.3 Notions à tracer

Pour chaque flux financier, il faut distinguer :

- montant brut
- frais passerelle
- frais plateforme
- montant net tenant
- montant remboursé
- montant restant à reverser

## 9.4 Conditions avant reversement

Avant tout reversement, le système doit pouvoir vérifier :

- tenant valide
- moyen de reversement configuré
- bénéficiaire validé si nécessaire
- absence de blocage conformité
- montant disponible cohérent
- absence de litige bloquant

## 9.5 Entités concernées

Côté plateforme :

- `platform_transactions`
- `payout_batches`
- `payout_lines`
- `refund_cases`
- `payment_incidents`

Côté tenant :

- `tenant_ledger_entries`
- `payout_expectations`
- `local_refund_records`

---

## 10. Tables impactées

## 10.1 Côté tenant

Tables principales :

- `payment_attempts`
- `payment_confirmations`
- `payment_transactions`
- `payment_transaction_lines`
- `tenant_ledger_entries`
- `invoices`
- `receipts`
- `payout_expectations`
- `local_refund_records`
- `reconciliation_logs`

## 10.2 Côté plateforme

Tables principales :

- `payment_gateways`
- `platform_transactions`
- `platform_fee_rules`
- `platform_fee_settlements`
- `payout_batches`
- `payout_lines`
- `refund_cases`
- `payment_incidents`
- `gateway_webhook_logs`

## 10.3 Champs critiques à prévoir

Pour les flux `Paystack`, il faut prévoir au minimum selon la table :

- `reference`
- `gateway_reference`
- `provider_reference`
- `external_customer_reference` si utile
- `external_payout_reference` si utile
- `status`
- `currency`
- `amount`
- `paid_at`
- `channel`
- `raw_payload` si nécessaire pour audit
- `source_type`
- `source_id`
- `tenant_id` ou équivalent logique

---

## 11. Enums et statuts impactés

## 11.1 PaymentStatus

Statuts recommandés :

- `initialized`
- `pending`
- `success`
- `failed`
- `abandoned`
- `reversed`
- `refunded`

## 11.2 OrderStatus

Statuts recommandés :

- `pending`
- `awaiting_payment`
- `paid`
- `failed`
- `cancelled`
- `refunded`
- `partially_refunded`
- `expired`

## 11.3 PayoutStatus

Statuts recommandés :

- `pending`
- `processing`
- `paid`
- `failed`
- `reversed`

## 11.4 RefundStatus

Statuts recommandés :

- `requested`
- `approved`
- `processing`
- `refunded`
- `rejected`

---

## 12. Events métier recommandés

- `OrderPaymentInitialized`
- `PaystackTransactionInitialized`
- `PaystackWebhookReceived`
- `PaystackTransactionVerified`
- `PaystackTransactionFailed`
- `OrderPaid`
- `RefundRequested`
- `RefundProcessed`
- `PaystackTransferRequested`
- `PaystackTransferCompleted`

---

## 13. Jobs recommandés

## 13.1 Encaissement

- `InitializePaystackTransactionJob`
- `VerifyPaystackTransactionJob`
- `ProcessPaystackWebhookJob`
- `RetryFailedPaymentVerificationJob`

## 13.2 Écritures et rapprochement

- `CreateTenantLedgerEntriesJob`
- `CreatePlatformTransactionJob`
- `BuildPaymentReconciliationSnapshotJob`

## 13.3 Reversements

- `CreatePayoutBatchJob`
- `DispatchPayoutBatchJob`
- `RetryFailedPayoutJob`

## 13.4 Suites métier

- `GenerateIssuedTicketsJob`
- `SendOrderConfirmationJob`
- `SendContributionReceiptJob`
- `SendTrainingEnrollmentConfirmationJob`

---

## 14. Services recommandés

- `PaymentService`
- `PaystackService`
- `PaystackWebhookService`
- `ReconciliationService`
- `LedgerService`
- `RefundService`
- `PayoutService`

## 14.1 Responsabilités attendues

### PaymentService

- point d’entrée métier commun pour les modules payants
- orchestration des intentions de paiement
- normalisation des statuts

### PaystackService

- initialisation des transactions
- vérification serveur
- adaptation des réponses de la passerelle

### PaystackWebhookService

- validation du webhook
- journalisation
- résolution de la ressource source
- déclenchement du traitement idempotent

### LedgerService

- génération des écritures de ventilation
- calcul brut / frais / net
- cohérence financière interne

### PayoutService

- calcul des montants à reverser
- préparation des batches
- suivi d’exécution et incidents

---

## 15. Incidents à prévoir

Le système doit prévoir des traitements explicites pour :

- paiement réussi sans émission de billet
- double webhook
- référence inconnue
- tentative expirée mais paiement tardif
- remboursement partiel
- reversement échoué
- écart entre ledger local et vue plateforme
- client débité sans confirmation métier

---

## 16. Monitoring et exploitation

## 16.1 Tableaux de bord recommandés

Côté plateforme :

- volume des transactions
- taux de succès
- taux d’échec
- incidents en attente
- reversements en attente
- reversements échoués
- montants dus aux tenants

Côté tenant :

- paiements du jour
- commandes payées
- commandes en échec
- remboursements
- reversements attendus

## 16.2 Alertes utiles

- webhook invalide
- pic d’échecs de paiement
- transaction non rapprochée
- reversement rejeté
- divergence montant attendu / montant confirmé

---

## 17. Recommandation finale

Oui, `Paystack` est cohérent avec l’architecture du projet `Ticket`.

La bonne stratégie pour partir proprement est :

- un socle de paiement centralisé
- confirmation par webhook + vérification serveur
- journalisation stricte
- idempotence partout
- ledger interne explicite
- reversements différés et contrôlés

Cette approche est la plus adaptée au contexte :

- multi-tenant
- plateforme administrée par panel central
- opérations locales par tenant
- besoin de traçabilité financière
- besoin de supervision plateforme
