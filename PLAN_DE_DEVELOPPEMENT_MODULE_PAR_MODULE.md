# Plan de Développement Module par Module du Projet Ticket

## 1. Objet du document

Ce document définit le plan de développement recommandé pour le projet `Ticket`.

Il s’appuie sur les documents suivants :

- `ARCHITECTURE_COMPLETE_PROJET.md`
- `ARCHITECTURE_DONNEES_COMPLETE.md`
- `MODULES_TABLES_RELATIONS_COMPLETE.md`
- `CONCEPTION_TECHNIQUE_DETAILLEE_COMPLETE.md`
- `INTEGRATION_PAYSTACK_COMPLETE.md`

L’objectif est de transformer la cible d’architecture en **ordre de réalisation concret**, sans perdre la cohérence multi-tenant, la sécurité et la maintenabilité.

---

## 2. Principes de planification

## 2.1 Règle générale

Le projet doit être développé dans l’ordre suivant :

- fondations techniques
- administration et sécurité
- socle public
- billetterie et paiements
- extensions métier
- gouvernance, finance avancée et optimisation

## 2.2 Logique de priorité

On développe d’abord ce qui débloque tous les autres modules :

- multi-tenant
- panels
- auth
- rôles et permissions
- projections publiques
- paiements communs
- catégories

## 2.3 Résultat attendu du plan

À la fin de ce plan, l’équipe doit pouvoir :

- découper les sprints
- préparer les migrations
- organiser les modèles Eloquent
- répartir les responsabilités backend, admin et frontend
- identifier clairement les dépendances entre modules

---

## 3. Vue d’ensemble de l’ordre recommandé

## Phase 1

- fondations multi-tenant
- panel plateforme
- panel tenant
- authentification
- RBAC

## Phase 2

- noyau organisation tenant
- catégories et tags
- projections publiques
- portail public de base

## Phase 3

- billetterie
- paiements `Paystack`
- émission de tickets
- QR code et contrôle d’accès

## Phase 4

- formations
- appels à projets
- crowdfunding
- stands et salons

## Phase 5

- notifications avancées
- reporting
- reversements
- modération
- support
- analytics
- optimisation

---

## 4. Module 1 : Fondations multi-tenant

## Objectif

Mettre en place le socle technique du projet.

## Contenu

- initialisation du backend Laravel
- configuration `Tenancy for Laravel v3`
- séparation base centrale / base tenant
- stratégie d’identification du tenant
- bootstrap des tenants
- conventions de nommage et de structure
- configuration des queues, cache et stockage

## Dépendances

- aucune, c’est le point de départ

## Tables principalement concernées

### Central

- `tenants`
- `tenant_profiles`
- `tenant_domains`
- `tenant_status_histories`
- `plans`
- `tenant_subscriptions`
- `platform_settings`

### Tenant

- `tenant_settings`

## Services / jobs à prévoir

### Services

- `TenantProvisioningService`
- `TenantLifecycleService`
- `PlatformSettingsService`

### Jobs

- job de provisioning tenant si asynchrone
- job de synchronisation initiale si nécessaire

## Résultat attendu

- un tenant peut être créé proprement
- un tenant a son propre contexte technique
- la séparation central / tenant est stable
- les prochains modules peuvent se brancher sans refonte

---

## 5. Module 2 : Panels d’administration, auth et RBAC

## Objectif

Mettre en place l’administration plateforme et l’administration tenant avec un contrôle d’accès fiable.

## Contenu

- création du panel plateforme
- création du panel tenant
- guards et providers d’authentification
- séparation des comptes plateforme / tenant / public
- intégration `spatie/laravel-permission`
- intégration `Filament Shield`
- rôles par défaut plateforme
- rôles par défaut tenant
- visibilité conditionnelle des ressources Filament

## Dépendances

- module 1

## Tables principalement concernées

### Central

- `platform_users`
- `platform_roles`
- `platform_permissions`
- `platform_user_roles`
- `platform_role_permissions`

### Tenant

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `user_invitations`
- `user_sessions`

## Services / jobs à prévoir

### Services

- `PlatformAuthorizationService`
- `TenantAuthorizationService`
- `RolePermissionService`
- `PanelNavigationVisibilityService`

### Jobs

- invitation utilisateur tenant si envoi asynchrone
- notification de création de compte si nécessaire

## Résultat attendu

- les 2 panels sont accessibles séparément
- un tenant peut créer ses utilisateurs
- un tenant peut attribuer des rôles et permissions
- les ressources visibles dans Filament dépendent des permissions

---

## 6. Module 3 : Noyau organisation tenant

## Objectif

Mettre en place le socle fonctionnel local de chaque organisation.

## Contenu

- profil organisation
- contacts
- réseaux sociaux
- branding local
- paramètres locaux
- préférences opérationnelles
- gestion documentaire de base

## Dépendances

- modules 1 et 2

## Tables principalement concernées

- `tenant_settings`
- `organization_profiles`
- `organization_social_links`
- `organization_contacts`
- `media`
- `documents` si séparés

## Services / jobs à prévoir

### Services

- `TenantPublicProfileService`
- `MediaService`
- `DocumentService`

### Jobs

- traitements médias si nécessaire

## Résultat attendu

- chaque tenant dispose d’un profil propre
- les données de présentation du tenant sont exploitables côté admin et côté public

---

## 7. Module 4 : Catégories, tags et référentiels partagés

## Objectif

Mettre en place les référentiels réutilisés par tous les modules métier.

## Contenu

- référentiel central `categories`
- éventuelle copie ou synchronisation côté tenant
- gestion des tags
- pays, villes, devises, langues, statuts publics
- conventions de slug et de tri

## Dépendances

- modules 1 et 2

## Tables principalement concernées

### Central

- `categories`
- `tags`
- `countries`
- `cities`
- `currencies`
- `languages`
- `resource_types`
- `public_statuses`
- `payment_method_types`

### Tenant

- `categories`
- `tags`

## Services / jobs à prévoir

### Services

- `CategoryCatalogService`
- `SlugGenerationService`

### Jobs

- job de synchronisation catégories central vers tenant si retenu

## Résultat attendu

- tous les futurs modules peuvent utiliser des catégories cohérentes
- le portail public peut filtrer les ressources correctement

---

## 8. Module 5 : Portail public de base et projections publiques

## Objectif

Construire le socle public unifié sans interroger directement toutes les bases tenants à chaque requête.

## Contenu

- index public central
- projections des organisateurs
- projections des événements
- structure de slug public
- page organisateur
- catalogue public initial
- publication / dépublication

## Dépendances

- modules 1 à 4

## Tables principalement concernées

### Central

- `public_resource_indexes`
- `public_events`
- `public_organizers`
- `featured_resources`
- `search_index_snapshots` si retenu

### Tenant

- tables source publiables selon module
- `publication_histories`

## Services / jobs à prévoir

### Services

- `PublicationService`
- `PublicProjectionService`
- `PublicSearchIndexService`

### Jobs

- `SyncPublicOrganizerProjectionJob`
- `SyncPublicEventProjectionJob`
- `RebuildTenantPublicProjectionJob`

## Résultat attendu

- la page publique centrale fonctionne
- la page organisateur fonctionne
- le mécanisme de projection est fiable avant la billetterie

---

## 9. Module 6 : Billetterie - cœur événementiel

## Objectif

Mettre en place le premier vrai module métier prioritaire.

## Contenu

- création d’événements
- dates et sessions
- lieux et salles
- médias
- catégories et tags d’événements
- types de billets
- quotas et stocks
- coupons et promotions
- panier et commande

## Dépendances

- modules 1 à 5

## Tables principalement concernées

- `events`
- `event_dates`
- `event_venues`
- `event_media`
- `event_tags`
- `event_categories`
- `ticket_types`
- `ticket_type_benefits`
- `coupons`
- `orders`
- `order_items`
- `order_status_histories`

## Services / jobs à prévoir

### Services

- `EventService`
- `TicketTypeService`
- `OrderService`

### Jobs

- jobs de recalcul stock si nécessaire
- jobs de confirmation de commande

## Résultat attendu

- un tenant peut créer et publier un événement
- un client peut choisir un billet et créer une commande
- le module est prêt à être branché au paiement

---

## 10. Module 7 : Paiements communs et intégration Paystack

## Objectif

Brancher le moteur de paiement commun à la billetterie puis aux autres modules.

## Contenu

- `payment_attempts`
- `payment_confirmations`
- `payment_transactions`
- ledger tenant
- finance plateforme
- intégration `Paystack`
- webhooks
- vérification serveur
- rapprochement
- reçus et factures
- incidents de paiement

## Dépendances

- modules 1 à 6

## Tables principalement concernées

### Tenant

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

### Central

- `payment_gateways`
- `platform_transactions`
- `platform_fee_rules`
- `platform_fee_settlements`
- `payout_batches`
- `payout_lines`
- `refund_cases`
- `payment_incidents`
- `gateway_webhook_logs`

## Services / jobs à prévoir

### Services

- `PaymentService`
- `PaystackService`
- `PaystackWebhookService`
- `LedgerService`
- `ReconciliationService`

### Jobs

- `InitializePaystackTransactionJob`
- `VerifyPaystackTransactionJob`
- `ProcessPaystackWebhookJob`
- `CreateTenantLedgerEntriesJob`
- `CreatePlatformTransactionJob`

## Résultat attendu

- une commande peut être payée de façon fiable
- la confirmation repose sur `webhook` + vérification serveur
- les écritures financières sont cohérentes
- les autres modules pourront réutiliser ce socle

---

## 11. Module 8 : Tickets émis, QR code et contrôle d’accès

## Objectif

Finaliser le cycle complet de la billetterie après paiement.

## Contenu

- émission de billets
- génération QR
- validation à l’entrée
- gestion du double scan
- invalidation
- historique de scan
- reporting terrain de base

## Dépendances

- modules 6 et 7

## Tables principalement concernées

- `issued_tickets`
- `ticket_qr_codes`
- `ticket_scans`
- `access_control_points`
- `scan_agents`

## Services / jobs à prévoir

### Services

- `TicketIssuanceService`
- `QrCodeService`
- `CheckInService`

### Jobs

- `GenerateIssuedTicketsJob`
- `GenerateTicketQrCodesJob`
- `InvalidateCancelledTicketsJob`

## Résultat attendu

- le cycle événementiel principal est exploitable de bout en bout
- le contrôle d’accès terrain est possible
- le premier produit commercialisable du projet existe

---

## 12. Module 9 : Formations

## Objectif

Étendre le socle à la gestion de sessions de formation.

## Contenu

- catalogue de formations
- sessions
- formateurs
- inscriptions
- présences
- certificats éventuels
- catégories et tags de formation

## Dépendances

- modules 1 à 7

## Tables principalement concernées

- `trainings`
- `training_sessions`
- `trainers`
- `training_trainers`
- `training_categories`
- `training_tags`
- `training_media`
- `training_enrollments`
- `attendance_records`
- `training_certificates`

## Services / jobs à prévoir

### Services

- `TrainingService`
- `TrainingEnrollmentService`
- `AttendanceService`
- `CertificateService`

### Jobs

- `SendTrainingEnrollmentConfirmationJob`
- `SendTrainingReminderJob`
- `GenerateTrainingCertificateJob`

## Résultat attendu

- un tenant peut publier une formation
- un participant peut s’inscrire et payer si requis
- les présences sont suivies

---

## 13. Module 10 : Appels à projets

## Objectif

Permettre aux tenants de gérer des appels à projets avec workflow de soumission et d’évaluation.

## Contenu

- publication d’appel
- lots éventuels
- formulaires dynamiques
- dépôt de candidature
- pièces jointes
- assignation des reviewers
- décisions
- historisation stricte

## Dépendances

- modules 1 à 5
- module 7 si frais de dossier ou paiement

## Tables principalement concernées

- `calls_for_projects`
- `call_lots`
- `call_categories`
- `call_tags`
- `call_form_sections`
- `call_form_fields`
- `applications`
- `application_answers`
- `application_files`
- `review_assignments`
- `review_decisions`
- `application_status_histories`

## Services / jobs à prévoir

### Services

- `CallForProjectsService`
- `ApplicationSubmissionService`
- `ReviewerAssignmentService`
- `EvaluationService`

### Jobs

- `SendApplicationReceivedNotificationJob`
- `AssignApplicationReviewersJob`
- `SendApplicationDecisionNotificationJob`

## Résultat attendu

- un tenant peut gérer tout le cycle d’un appel à projets
- les reviewers travaillent dans un cadre sécurisé et historisé

---

## 14. Module 11 : Crowdfunding

## Objectif

Permettre la collecte de fonds autour de campagnes portées par les tenants.

## Contenu

- campagnes
- objectifs financiers
- paliers
- contributions
- reçus
- progression publique
- catégories et tags

## Dépendances

- modules 1 à 5
- module 7 pour les paiements

## Tables principalement concernées

- `campaigns`
- `campaign_categories`
- `campaign_tags`
- `campaign_media`
- `campaign_milestones`
- `contributions`
- `contribution_status_histories`

## Services / jobs à prévoir

### Services

- `CampaignService`
- `ContributionService`
- `CampaignProgressService`

### Jobs

- `SendContributionReceiptJob`
- `UpdateCampaignProgressJob`
- `NotifyCampaignMilestoneReachedJob`

## Résultat attendu

- une campagne peut être publiée et financée
- la progression est fiable côté public
- les contributions sont réconciliées avec le socle paiement

---

## 15. Module 12 : Stands et salons

## Objectif

Gérer les salons, halls, zones et réservations de stands.

## Contenu

- salons
- halls et zones
- stands
- disponibilité
- exposants
- documents exposants
- réservations
- échéancier de paiement
- catégories et tags

## Dépendances

- modules 1 à 5
- module 7 pour les paiements

## Tables principalement concernées

- `salons`
- `salon_categories`
- `salon_tags`
- `halls`
- `zones`
- `stands`
- `stand_prices`
- `exhibitors`
- `exhibitor_documents`
- `stand_reservations`
- `stand_reservation_items`
- `stand_payment_schedules`
- `salon_status_histories`

## Services / jobs à prévoir

### Services

- `SalonService`
- `StandReservationService`
- `ExhibitorService`

### Jobs

- `ExpireStandReservationJob`
- `SendStandReservationConfirmationJob`
- `GenerateExhibitorDocumentsJob`

## Résultat attendu

- un tenant peut vendre et gérer des stands
- la disponibilité et les paiements sont traçables

---

## 16. Module 13 : Notifications transverses

## Objectif

Centraliser les communications système et métier.

## Contenu

- emails transactionnels
- notifications internes admin
- rappels
- confirmations
- alertes incident
- gabarits de notification

## Dépendances

- modules 1 à 12 selon les cas d’usage

## Tables principalement concernées

- `notification_templates`
- `notification_logs`
- `notification_preferences`
- `delivery_attempts`

## Services / jobs à prévoir

### Services

- `NotificationService`

### Jobs

- `DispatchNotificationJob`
- jobs d’envoi spécialisés selon module

## Résultat attendu

- les modules peuvent notifier sans dupliquer la logique
- les envois sont traçables

---

## 17. Module 14 : Support, audit et conformité

## Objectif

Mettre en place la gouvernance opérationnelle et la traçabilité sensible.

## Contenu

- audit des actions sensibles
- logs de changement de rôle
- logs de publication
- tickets support tenant
- tickets support plateforme
- conformité documentaire
- suivi des incidents

## Dépendances

- modules 1 et 2, puis enrichissement progressif

## Tables principalement concernées

### Tenant

- `audit_logs`
- `publication_histories`
- `role_change_logs`
- `sensitive_action_logs`
- `support_ticket_tenant`
- `incident_logs`

### Central

- `moderation_cases`
- `moderation_decisions`
- `tenant_verification_files`
- `support_ticket_platform`
- `privacy_requests`
- `compliance_event_logs`

## Services / jobs à prévoir

### Services

- `AuditService`
- `DocumentService`

### Jobs

- notifications d’incident et de conformité

## Résultat attendu

- les opérations sensibles sont traçables
- la plateforme peut superviser les tenants

---

## 18. Module 15 : Reporting, finance avancée et reversements

## Objectif

Mettre en place la lecture consolidée du business et les flux financiers plateforme avancés.

## Contenu

- tableaux de bord tenant
- KPIs plateforme
- snapshots quotidiens
- commissions
- reversements aux tenants
- suivi des incidents de paiement
- export financier
- rapprochement avancé

## Dépendances

- modules 7 à 14

## Tables principalement concernées

- `platform_transactions`
- `platform_fee_settlements`
- `payout_batches`
- `payout_lines`
- `refund_cases`
- `payment_incidents`
- `tenant_ledger_entries`
- `reconciliation_logs`
- tables de snapshots si retenues

## Services / jobs à prévoir

### Services

- `PayoutService`
- `ReconciliationService`
- `ReportSnapshotService`
- `ExportService`

### Jobs

- `CreatePayoutBatchJob`
- `BuildDailyKpisJob`
- `BuildSalesSnapshotsJob`
- jobs d’export

## Résultat attendu

- la plateforme peut piloter le business
- les reversements sont industrialisés
- les tenants disposent de reporting fiable

---

## 19. Module 16 : Recherche, performance et optimisation

## Objectif

Renforcer la performance, la qualité d’exploitation et l’expérience utilisateur.

## Contenu

- optimisation des indexes
- cache du catalogue public
- cache des pages organisateur
- optimisation des requêtes admin
- moteur de recherche enrichi si retenu
- reprise sur incident
- jobs de maintenance

## Dépendances

- tous les modules cœur doivent déjà être stables

## Tables principalement concernées

- projections publiques
- snapshots
- logs techniques
- files de maintenance

## Services / jobs à prévoir

### Services

- `PublicSearchIndexService`
- `ReportSnapshotService`

### Jobs

- `RetryFailedProjectionJobsJob`
- `RetryFailedNotificationJobsJob`
- `CleanupTemporaryFilesJob`

## Résultat attendu

- la plateforme tient la charge
- le portail public reste fluide
- les opérations récurrentes sont industrialisées

---

## 20. Recommandation d’ordre de livraison réel

## Lot 1 : produit de base vendable

- modules 1 à 8

Cela donne :

- multi-tenant
- panels
- RBAC
- portail public
- événements
- paiement
- ticketing
- QR / scan

## Lot 2 : enrichissement métier

- modules 9 à 12

Cela donne :

- formations
- appels à projets
- crowdfunding
- stands

## Lot 3 : industrialisation plateforme

- modules 13 à 16

Cela donne :

- notifications avancées
- support
- audit
- reporting
- reversements
- optimisation

---

## 21. Recommandation d’organisation de travail

## Équipe backend

Doit piloter :

- multi-tenant
- modèles Eloquent
- services
- jobs
- webhooks
- sécurité

## Équipe admin Filament

Doit piloter :

- resources
- forms
- tables
- dashboards
- policies d’accès
- ergonomie des panels

## Équipe frontend public

Doit piloter :

- catalogue public
- pages organisateur
- pages détail événement / formation / campagne / salon
- tunnel d’achat ou d’inscription

---

## 22. Prochaine suite logique après ce document

Après ce plan, la suite la plus rationnelle est :

- détailler les **champs exacts table par table**
- préparer la **priorisation des migrations**
- définir les **resources Filament** module par module
- préparer les **user stories** et les **sprints**

---

## 23. Conclusion

Le projet `Ticket` peut maintenant être abordé de manière structurée.

Le bon ordre n’est pas de coder module par module de façon isolée, mais de suivre cette progression :

- fondations
- sécurité et administration
- socle public
- billetterie + paiements
- extensions métier
- industrialisation plateforme

C’est cette approche qui réduit le risque de refonte et garde une cohérence forte entre :

- architecture
- modèle de données
- administration Filament
- frontend public
- finance
- exploitation
