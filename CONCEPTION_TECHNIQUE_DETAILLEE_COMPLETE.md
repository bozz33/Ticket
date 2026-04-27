# Conception Technique Détaillée Complémentaire du Projet Ticket

## 1. Objet du document

Ce document complète les documents suivants :

- `ARCHITECTURE_COMPLETE_PROJET.md`
- `ARCHITECTURE_DONNEES_COMPLETE.md`
- `MODULES_TABLES_RELATIONS_COMPLETE.md`
- `INTEGRATION_PAYSTACK_COMPLETE.md`

Il formalise les éléments techniques qui n’étaient pas encore figés en détail :

- stack validée
- architecture d’administration
- stratégie RBAC
- enums et statuts
- events métier
- jobs asynchrones
- services applicatifs
- optimisations, indexes et contraintes

---

## 2. Décisions techniques validées

## 2.1 Stack retenue

- backend : `Laravel`
- multi-tenant : `Tenancy for Laravel v3`
- administration : `Filament 5`
- rôles/permissions : `spatie/laravel-permission`
- intégration Filament des permissions : `Filament Shield`
- paiement principal : `Paystack`
- frontend public : portail unifié

## 2.2 Architecture admin retenue

Le projet utilise **2 panels Filament**.

### Panel plateforme

Utilisé par :

- le créateur de la plateforme
- le super-admin
- l’équipe plateforme

Responsabilités :

- gestion des tenants
- modération
- finance plateforme
- projections publiques
- configuration globale
- support plateforme

### Panel tenant

Utilisé par :

- chaque organisation
- ses administrateurs
- ses managers métier

Responsabilités :

- gestion des users du tenant
- gestion des rôles et permissions
- gestion des modules métier du tenant
- reporting local
- opérations quotidiennes

## 2.3 Stratégie RBAC retenue

La stratégie recommandée est :

- `spatie/laravel-permission` comme fondation
- `Filament Shield` comme intégration de sécurité Filament
- logique métier complémentaire en code uniquement si nécessaire

### Conséquences

- un tenant peut créer ses propres users
- un tenant peut affecter des rôles à ses users
- les ressources Filament sont affichées selon les permissions
- les pages, widgets, actions et formulaires sont restreints selon les permissions
- la plateforme garde son propre espace d’administration séparé

---

## 3. Guards, modèles utilisateurs et séparation des accès

## 3.1 Comptes distincts

Le système distingue :

- les comptes plateforme
- les comptes administration tenant
- les comptes publics utilisateurs

## 3.2 Recommandation de séparation

### Plateforme

- modèle principal : `PlatformUser`
- authentification dédiée au panel plateforme
- permissions du panel plateforme indépendantes du tenant

### Tenant

- modèle principal : `User`
- authentification dédiée au panel tenant
- rôles et permissions propres à chaque tenant

### Public

- modèle principal : `PublicUser`
- utilisé pour les achats, inscriptions, contributions et candidatures si un compte unifié est retenu

## 3.3 RBAC physique recommandé

### Côté tenant

Tables recommandées compatibles avec `spatie/laravel-permission` :

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

### Côté plateforme

Deux options sont possibles :

- réutiliser la même convention `roles/permissions/...` dans la base centrale
- ou conserver des noms dédiés comme `platform_roles`, `platform_permissions` si une séparation plus explicite est souhaitée

La recommandation pragmatique est :

- **tenant : convention Spatie par défaut**
- **plateforme : convention claire et indépendante**, tant que l’implémentation reste cohérente

---

## 4. Enums et statuts recommandés

Les enums ci-dessous doivent être implémentés progressivement. Ils servent à standardiser les états métier, limiter les erreurs et simplifier les requêtes.

# 4.1 Enums globaux

## TenantStatus

- `draft`
- `active`
- `suspended`
- `archived`

## PublicationStatus

- `draft`
- `pending_review`
- `published`
- `unpublished`
- `archived`
- `suspended`

## Visibility

- `public`
- `private`
- `unlisted`

## YesNoState si nécessaire

À éviter si possible, préférer des booléens explicites.

# 4.2 Billetterie

## EventMode

- `physical`
- `online`
- `hybrid`

## EventStatus

- `draft`
- `scheduled`
- `onsale`
- `sold_out`
- `completed`
- `cancelled`
- `postponed`
- `archived`

## TicketTypeStatus

- `draft`
- `active`
- `paused`
- `sold_out`
- `disabled`

## OrderStatus

- `pending`
- `awaiting_payment`
- `paid`
- `failed`
- `cancelled`
- `refunded`
- `partially_refunded`
- `expired`

## PaymentStatus

- `initialized`
- `pending`
- `success`
- `failed`
- `abandoned`
- `reversed`
- `refunded`

## TicketStatus

- `issued`
- `used`
- `cancelled`
- `invalidated`
- `refunded`

## ScanResult

- `valid`
- `already_used`
- `invalid`
- `blocked`
- `expired`
- `wrong_event`

# 4.3 Formations

## TrainingStatus

- `draft`
- `published`
- `closed`
- `cancelled`
- `completed`
- `archived`

## TrainingEnrollmentStatus

- `pending`
- `confirmed`
- `waitlisted`
- `cancelled`
- `completed`
- `no_show`

# 4.4 Appels à projets

## ApplicationStatus

- `draft`
- `submitted`
- `under_review`
- `shortlisted`
- `accepted`
- `rejected`
- `withdrawn`
- `archived`

## ReviewDecisionStatus

- `pending`
- `approved`
- `rejected`
- `needs_revision`

# 4.5 Crowdfunding

## CampaignStatus

- `draft`
- `active`
- `paused`
- `completed`
- `cancelled`
- `archived`

## ContributionStatus

- `pending`
- `paid`
- `failed`
- `refunded`
- `reversed`

# 4.6 Stands

## SalonStatus

- `draft`
- `published`
- `open_for_booking`
- `closed`
- `completed`
- `cancelled`

## StandReservationStatus

- `pending`
- `reserved`
- `partially_paid`
- `paid`
- `cancelled`
- `expired`

# 4.7 Finance

## PayoutStatus

- `pending`
- `processing`
- `paid`
- `failed`
- `reversed`

## RefundStatus

- `requested`
- `approved`
- `processing`
- `refunded`
- `rejected`

---

## 5. Events métier recommandés

Les events permettent de découpler les modules et de déclencher jobs, projections, notifications et audits.

# 5.1 Tenant / plateforme

- `TenantCreated`
- `TenantActivated`
- `TenantSuspended`
- `TenantArchived`
- `TenantProfileUpdated`

# 5.2 Publication / projection publique

- `PublicResourcePublishRequested`
- `PublicResourcePublished`
- `PublicResourceUnpublished`
- `PublicProjectionSyncRequested`
- `PublicProjectionSynced`
- `PublicProjectionSyncFailed`

# 5.3 Billetterie

- `EventCreated`
- `EventPublished`
- `EventUpdated`
- `EventCancelled`
- `TicketTypeCreated`
- `OrderCreated`
- `OrderPaymentInitialized`
- `OrderPaid`
- `OrderFailed`
- `TicketIssued`
- `TicketInvalidated`
- `TicketScanned`
- `RefundRequested`
- `RefundProcessed`

# 5.4 Formations

- `TrainingPublished`
- `TrainingEnrollmentCreated`
- `TrainingEnrollmentConfirmed`
- `TrainingAttendanceRecorded`

# 5.5 Appels à projets

- `CallPublished`
- `ApplicationSubmitted`
- `ApplicationUnderReview`
- `ApplicationDecisionRecorded`

# 5.6 Crowdfunding

- `CampaignPublished`
- `ContributionCreated`
- `ContributionPaid`
- `CampaignMilestoneReached`

# 5.7 Stands

- `SalonPublished`
- `StandReserved`
- `StandReservationExpired`
- `StandReservationPaid`

# 5.8 Paiements Paystack

- `PaystackTransactionInitialized`
- `PaystackWebhookReceived`
- `PaystackTransactionVerified`
- `PaystackTransactionFailed`
- `PaystackTransferRequested`
- `PaystackTransferCompleted`

---

## 6. Jobs asynchrones recommandés

Les jobs doivent être idempotents, rejouables et journalisés.

# 6.1 Jobs de projection publique

- `SyncPublicOrganizerProjectionJob`
- `SyncPublicEventProjectionJob`
- `SyncPublicTrainingProjectionJob`
- `SyncPublicCallProjectionJob`
- `SyncPublicCampaignProjectionJob`
- `SyncPublicSalonProjectionJob`
- `RebuildTenantPublicProjectionJob`

# 6.2 Jobs de paiement

- `InitializePaystackTransactionJob`
- `VerifyPaystackTransactionJob`
- `ProcessPaystackWebhookJob`
- `CreateTenantLedgerEntriesJob`
- `CreatePlatformTransactionJob`
- `CreatePayoutBatchJob`
- `RetryFailedPaymentVerificationJob`

# 6.3 Jobs de billetterie

- `GenerateIssuedTicketsJob`
- `GenerateTicketQrCodesJob`
- `SendOrderConfirmationJob`
- `SendTicketDeliveryJob`
- `ExpirePendingOrdersJob`
- `InvalidateCancelledTicketsJob`

# 6.4 Jobs de formation

- `SendTrainingEnrollmentConfirmationJob`
- `SendTrainingReminderJob`
- `GenerateTrainingCertificateJob`

# 6.5 Jobs d’appels à projets

- `SendApplicationReceivedNotificationJob`
- `AssignApplicationReviewersJob`
- `SendApplicationDecisionNotificationJob`

# 6.6 Jobs de crowdfunding

- `SendContributionReceiptJob`
- `UpdateCampaignProgressJob`
- `NotifyCampaignMilestoneReachedJob`

# 6.7 Jobs de stands

- `ExpireStandReservationJob`
- `SendStandReservationConfirmationJob`
- `GenerateExhibitorDocumentsJob`

# 6.8 Jobs transverses

- `DispatchNotificationJob`
- `BuildDailyKpisJob`
- `BuildSalesSnapshotsJob`
- `CleanupTemporaryFilesJob`
- `RetryFailedProjectionJobsJob`
- `RetryFailedNotificationJobsJob`

---

## 7. Services applicatifs recommandés

Les services structurent le code métier et évitent de disperser la logique dans les contrôleurs, resources ou modèles.

# 7.1 Services de plateforme

- `TenantProvisioningService`
- `TenantLifecycleService`
- `TenantPublicProfileService`
- `PlatformSettingsService`
- `CategoryCatalogService`

# 7.2 Services RBAC / sécurité

- `PlatformAuthorizationService`
- `TenantAuthorizationService`
- `RolePermissionService`
- `PanelNavigationVisibilityService`

# 7.3 Services de publication / projection

- `PublicationService`
- `PublicProjectionService`
- `PublicSearchIndexService`
- `SlugGenerationService`

# 7.4 Services de paiement

- `PaymentService`
- `PaystackService`
- `PaystackWebhookService`
- `RefundService`
- `PayoutService`
- `LedgerService`
- `ReconciliationService`

# 7.5 Services de billetterie

- `EventService`
- `TicketTypeService`
- `OrderService`
- `TicketIssuanceService`
- `QrCodeService`
- `CheckInService`

# 7.6 Services de formation

- `TrainingService`
- `TrainingEnrollmentService`
- `AttendanceService`
- `CertificateService`

# 7.7 Services d’appels à projets

- `CallForProjectsService`
- `ApplicationSubmissionService`
- `ReviewerAssignmentService`
- `EvaluationService`

# 7.8 Services de crowdfunding

- `CampaignService`
- `ContributionService`
- `CampaignProgressService`

# 7.9 Services de stands

- `SalonService`
- `StandReservationService`
- `ExhibitorService`

# 7.10 Services transverses

- `NotificationService`
- `MediaService`
- `DocumentService`
- `AuditService`
- `ReportSnapshotService`
- `ExportService`

---

## 8. Optimisations, indexes et contraintes

## 8.1 Contraintes d’unicité minimales

### Central

- `tenants.public_id` unique
- `tenant_profiles.slug` unique
- `categories.slug` unique par scope
- `public_resource_indexes` unique sur `resource_type + resource_public_id`
- `public_events.slug` unique
- `public_formations.slug` unique
- `public_calls.slug` unique
- `public_campaigns.slug` unique
- `public_salons.slug` unique
- `platform_transactions.external_reference` unique si fournie

### Tenant

- `events.public_id` unique
- `events.slug` unique dans le tenant
- `ticket_types.public_id` unique
- `orders.reference` unique
- `issued_tickets.ticket_number` unique
- `issued_tickets.qr_token` unique
- `coupons.code` unique dans le contexte défini
- `payment_attempts.reference` unique
- `payment_transactions.gateway_reference` unique si fournie

## 8.2 Indexes prioritaires central

- `tenant_public_id`
- `publication_status`
- `visibility`
- `resource_type`
- `slug`
- `starts_at`
- `country_code`
- `city_name`
- `category_id` ou `category_slug`

## 8.3 Indexes prioritaires tenant

### Billetterie

- `events.slug`
- `events.publication_status`
- `event_dates.starts_at`
- `orders.reference`
- `orders.status`
- `orders.public_user_id`
- `issued_tickets.qr_token`
- `ticket_scans.ticket_id`
- `ticket_scans.scan_at`

### Paiements

- `payment_attempts.reference`
- `payment_attempts.status`
- `payment_transactions.gateway_reference`
- `tenant_ledger_entries.source_type + source_id`

### Appels à projets

- `applications.status`
- `applications.call_for_projects_id`
- `review_assignments.reviewer_id`

### Crowdfunding

- `contributions.status`
- `contributions.campaign_id`

### Stands

- `stand_reservations.status`
- `stand_reservations.exhibitor_id`

## 8.4 Idempotence obligatoire

Les traitements suivants doivent être idempotents :

- webhooks Paystack
- vérification de transaction
- génération de tickets
- synchronisation de projection publique
- création d’écritures ledger
- reversements

## 8.5 Stratégie de cache

À mettre en cache :

- homepage publique
- listings publics filtrés fréquents
- catégories
- page organisateur publique
- projections publiques résumées
- facettes de filtres

## 8.6 Stratégie de queue

Queues recommandées :

- `projections`
- `payments`
- `notifications`
- `tickets`
- `reports`
- `maintenance`

## 8.7 Soft delete et archivage

Soft delete recommandé pour :

- événements
- formations
- campagnes
- salons
- documents métier
- users tenant selon politique interne

Archivage logique recommandé pour les entités financières et historiques plutôt qu’une suppression.

---

## 9. Règles spécifiques Paystack

## 9.1 Règle de confirmation de paiement

Le système ne doit pas délivrer de valeur uniquement sur un callback navigateur.

La confirmation de paiement doit reposer prioritairement sur :

- webhook Paystack
- vérification serveur de la transaction

## 9.2 Références à stocker

Pour chaque paiement Paystack, il faut prévoir :

- `reference`
- `gateway_reference` si distincte
- `authorization_reference` si fournie
- `channel`
- `currency`
- `amount`
- `status`
- `paid_at`
- `customer_email`
- payload brut si nécessaire pour audit

## 9.3 Webhooks

Chaque webhook doit être :

- journalisé
- vérifié
- idempotent
- rejouable
- corrélé à une ressource métier source

---

## 10. Règles spécifiques Filament 5

## 10.1 Panel plateforme

Le panel plateforme doit contenir :

- navigation globale
- ressources tenant
- ressources finance plateforme
- modération
- support
- dashboards globaux

## 10.2 Panel tenant

Le panel tenant doit contenir :

- users
- roles
- permissions
- catégories locales si retenues
- modules métier du tenant
- dashboards locaux

## 10.3 Visibilité des ressources

La visibilité doit être pilotée par permissions pour :

- navigation
- accès liste
- accès création
- accès édition
- actions de table
- widgets
- pages personnalisées

---

## 11. Ordre recommandé d’implémentation technique

## 11.1 Fondations

- multi-tenant
- panel plateforme
- panel tenant
- auth et guards
- roles/permissions
- catégories

## 11.2 Socle public

- projections publiques
- slugs
- page organisateur
- catalogue public de base

## 11.3 Billetterie et paiements

- events
- ticket types
- orders
- paystack
- tickets
- qr / scan

## 11.4 Extensions métier

- formations
- appels à projets
- crowdfunding
- stands

## 11.5 Gouvernance et optimisation

- notifications avancées
- reporting
- reversements
- modération fine
- analytics avancées

---

## 12. Conclusion

Avec ce document, la conception est maintenant suffisamment détaillée pour :

- lancer un plan de développement module par module
- préparer les migrations et modèles
- structurer les services applicatifs
- organiser les jobs, webhooks et projections
- sécuriser la gestion des rôles, paiements et accès

Les documents d’architecture précédents restent valides, et ce document sert de couche complémentaire de normalisation technique.
