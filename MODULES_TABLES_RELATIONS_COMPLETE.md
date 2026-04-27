# Modules, Tables et Relations du Projet Ticket

## 1. Objet du document

Ce document liste le modèle fonctionnel recommandé pour le projet `Ticket` :

- les modules
- les tables principales
- les relations principales
- la séparation entre `central` et `tenant`
- la place de la table `categories`
- l’alignement avec la couche technique détaillée décrite dans `CONCEPTION_TECHNIQUE_DETAILLEE_COMPLETE.md`

Ce document reste un **schéma logique cible**. Il servira ensuite à produire :

- le schéma SQL détaillé
- les migrations Laravel
- les modèles Eloquent
- les règles de permissions

---

## 2. Règles générales de modélisation

## 2.1 Deux niveaux de base

### Base centrale

Elle contient :

- la plateforme
- les tenants
- les référentiels globaux
- les projections publiques
- la finance plateforme
- la modération globale
- les comptes publics unifiés si retenus

### Base tenant

Elle contient :

- les données métier opérationnelles du tenant
- les équipes locales
- les modules métier
- les transactions détaillées
- les journaux locaux

## 2.2 Convention de nommage

Je recommande :

- noms de tables au pluriel
- pivots explicites
- pas de tables trop polymorphiques pour les modules critiques
- foreign keys explicites dès que la base est la même

## 2.3 Identifiants

Chaque grande table doit prévoir :

- `id`
- `public_id` si la ressource est exposée publiquement
- `slug` pour les ressources publiques concernées
- `created_at`
- `updated_at`
- `deleted_at` lorsque la suppression logique est nécessaire

---

# 3. Table `categories`

## 3.1 Recommandation

Oui, il faut une vraie table `categories`.

Je recommande le modèle suivant :

- `categories` en **central** comme référentiel maître
- `categories` en **tenant** comme référentiel exploitable localement si on veut des foreign keys locales et des performances simples
- des **pivots dédiés par module**

## 3.2 Table centrale `categories`

### Champs principaux

- `id`
- `public_id`
- `parent_id`
- `name`
- `slug`
- `description`
- `resource_type_scope`
- `is_active`
- `sort_order`
- `created_at`
- `updated_at`
- `deleted_at`

## 3.3 Relations de `categories`

- une catégorie peut avoir **une catégorie parente**
- une catégorie peut avoir **plusieurs sous-catégories**
- une catégorie peut être utilisée par plusieurs modules

## 3.4 Tables pivot recommandées

- `event_categories`
- `training_categories`
- `call_categories`
- `campaign_categories`
- `salon_categories`

## 3.5 Pourquoi je recommande des pivots par module

Parce que c’est :

- plus clair
- plus facile à requêter
- plus simple à sécuriser
- plus simple à maintenir avec Laravel et Filament

---

# 4. Vue globale des modules

Les modules principaux sont :

- plateforme centrale
- tenants / organisations
- comptes publics utilisateurs
- référentiels globaux
- portail public et projections
- billetterie
- paiements et finance
- QR code et contrôle d’accès
- formations
- appels à projets
- crowdfunding
- stands
- notifications
- support
- médias et documents
- audit et historisation
- reporting et analytics

---

# 5. Modules centraux

# 5.1 Module : plateforme centrale

## Tables principales

- `tenants`
- `tenant_profiles`
- `tenant_domains`
- `tenant_status_histories`
- `plans`
- `tenant_subscriptions`
- `platform_users`
- `platform_roles`
- `platform_permissions`
- `platform_user_roles`
- `platform_role_permissions`
- `payment_gateways`
- `platform_fee_rules`
- `platform_settings`

## Relations principales

- un `tenant` a un `tenant_profile`
- un `tenant` a plusieurs `tenant_domains`
- un `tenant` a plusieurs `tenant_status_histories`
- un `tenant` peut avoir un `plan`
- un `tenant` peut avoir plusieurs `tenant_subscriptions`
- un `platform_user` a plusieurs `platform_roles` via `platform_user_roles`
- un `platform_role` a plusieurs `platform_permissions` via `platform_role_permissions`
- un `payment_gateway` peut être utilisé par plusieurs `platform_fee_rules`

---

# 5.2 Module : référentiels globaux

## Tables principales

- `categories`
- `tags`
- `countries`
- `cities`
- `currencies`
- `languages`
- `resource_types`
- `public_statuses`
- `payment_method_types`

## Relations principales

- une `category` peut appartenir à une autre `category` via `parent_id`
- une `city` appartient à un `country`
- une `currency` peut être utilisée par plusieurs tenants et ressources
- un `tag` peut être répliqué ou projeté selon les besoins des modules

---

# 5.3 Module : comptes publics utilisateurs

## Tables principales

- `public_users`
- `public_user_profiles`
- `public_user_addresses`
- `public_user_consents`
- `public_user_authentications`
- `public_user_sessions`

## Relations principales

- un `public_user` a un `public_user_profile`
- un `public_user` a plusieurs `public_user_addresses`
- un `public_user` a plusieurs `public_user_consents`
- un `public_user` a plusieurs `public_user_authentications`
- un `public_user` a plusieurs `public_user_sessions`

---

# 5.4 Module : portail public et projections

## Tables principales

- `public_resource_indexes`
- `public_organizers`
- `public_events`
- `public_event_dates`
- `public_formations`
- `public_calls`
- `public_campaigns`
- `public_salons`
- `public_search_indexes`
- `public_featured_contents`
- `projection_sync_logs`

## Relations principales

- un `public_resource_index` appartient à un `tenant`
- un `public_organizer` appartient à un `tenant`
- un `public_event` appartient à un `tenant`
- un `public_event` a plusieurs `public_event_dates`
- un `public_formation` appartient à un `tenant`
- un `public_call` appartient à un `tenant`
- un `public_campaign` appartient à un `tenant`
- un `public_salon` appartient à un `tenant`
- une ressource publique peut avoir plusieurs entrées dans `projection_sync_logs`

---

# 5.5 Module : modération et conformité centrale

## Tables principales

- `moderation_cases`
- `moderation_decisions`
- `tenant_verification_documents`
- `compliance_events`
- `privacy_requests`

## Relations principales

- un `moderation_case` appartient à un `tenant`
- un `moderation_case` peut viser une ressource publique
- un `moderation_case` a plusieurs `moderation_decisions`
- un `tenant` a plusieurs `tenant_verification_documents`
- un `public_user` peut avoir plusieurs `privacy_requests`

---

# 5.6 Module : finance plateforme

## Tables principales

- `platform_transactions`
- `platform_transaction_lines`
- `payout_batches`
- `payout_lines`
- `refund_cases`
- `gateway_webhook_logs`
- `payment_incidents`

## Relations principales

- une `platform_transaction` appartient à un `tenant`
- une `platform_transaction` a plusieurs `platform_transaction_lines`
- un `payout_batch` a plusieurs `payout_lines`
- un `payout_line` appartient à un `tenant`
- un `refund_case` peut viser une transaction ou une ressource source
- un `gateway_webhook_log` appartient à un `payment_gateway`

---

# 5.7 Module : support plateforme

## Tables principales

- `platform_support_tickets`
- `platform_support_messages`
- `platform_support_attachments`

## Relations principales

- un `platform_support_ticket` appartient à un `tenant` ou à un `public_user`
- un `platform_support_ticket` a plusieurs `platform_support_messages`
- un `platform_support_message` a plusieurs `platform_support_attachments`

---

# 5.8 Module : audit central

## Tables principales

- `platform_audit_logs`
- `authentication_logs`
- `async_job_logs`
- `critical_error_logs`

## Relations principales

- un `platform_audit_log` peut référencer un `platform_user`, un `tenant` et une entité cible
- un `authentication_log` peut appartenir à un `platform_user` ou un `public_user`

---

# 6. Modules tenant

# 6.1 Module : noyau tenant / organisation

## Tables principales

- `tenant_settings`
- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `user_invitations`
- `user_sessions`
- `organization_profiles`
- `organization_social_links`
- `organization_contacts`
- `categories`
- `tags`

## Relations principales

- un `user` a plusieurs `roles` via `model_has_roles`
- un `user` peut avoir plusieurs `permissions` via `model_has_permissions`
- un `role` a plusieurs `permissions` via `role_has_permissions`
- un `organization_profile` a plusieurs `organization_social_links`
- un `organization_profile` a plusieurs `organization_contacts`
- une `category` peut avoir une catégorie parente via `parent_id`

## Remarque

La table `categories` du tenant peut être :

- une copie synchronisée du central
- ou un référentiel local enrichi selon les besoins du tenant
- les ressources Filament du panel tenant doivent être visibles selon les permissions via `spatie/laravel-permission` et `Filament Shield`

---

# 6.2 Module : billetterie

## Tables principales

- `events`
- `event_dates`
- `event_venues`
- `event_media`
- `event_tags`
- `event_categories`
- `ticket_types`
- `ticket_type_benefits`
- `orders`
- `order_items`
- `attendees`
- `issued_tickets`
- `ticket_transfers`
- `coupons`
- `coupon_usages`
- `refund_requests`
- `refund_items`
- `event_status_histories`

## Relations principales

- un `event` appartient à un `organization_profile` logique du tenant
- un `event` a plusieurs `event_dates`
- un `event` peut avoir un `event_venue`
- un `event` a plusieurs `event_media`
- un `event` a plusieurs `tags` via `event_tags`
- un `event` a plusieurs `categories` via `event_categories`
- un `event` a plusieurs `ticket_types`
- un `ticket_type` a plusieurs `ticket_type_benefits`
- une `order` appartient à un `public_user` ou à un profil client local
- une `order` a plusieurs `order_items`
- un `order_item` appartient à un `ticket_type`
- une `order` a plusieurs `attendees`
- un `attendee` peut avoir un `issued_ticket`
- un `issued_ticket` appartient à un `ticket_type`
- un `issued_ticket` peut avoir plusieurs `ticket_transfers`
- un `coupon` peut être lié à un `event` ou à plusieurs `events` selon la règle retenue
- un `coupon` a plusieurs `coupon_usages`
- une `refund_request` appartient à une `order`
- une `refund_request` a plusieurs `refund_items`
- un `event` a plusieurs `event_status_histories`

---

# 6.3 Module : paiements et finance tenant

## Tables principales

- `payment_attempts`
- `payment_confirmations`
- `payment_transactions`
- `payment_transaction_lines`
- `tenant_ledger_entries`
- `invoices`
- `receipts`
- `payout_expectations`
- `reconciliation_logs`

## Relations principales

- un `payment_attempt` appartient à une `order`, une `training_enrollment`, une `contribution` ou une `stand_reservation`
- un `payment_attempt` peut avoir plusieurs `payment_confirmations`
- une `payment_transaction` appartient à une ressource métier source
- une `payment_transaction` a plusieurs `payment_transaction_lines`
- une `invoice` appartient à une ressource payante
- un `receipt` appartient à une transaction ou à une ressource payée
- un `tenant_ledger_entry` appartient à une ressource financière source
- un `payout_expectation` appartient à une transaction ou à un groupe de transactions

---

# 6.4 Module : QR code et contrôle d’accès

## Tables principales

- `access_gates`
- `scanner_devices`
- `ticket_scans`
- `scan_incidents`

## Relations principales

- un `access_gate` appartient à un `event`
- un `scanner_device` peut être affecté à un `access_gate`
- un `ticket_scan` appartient à un `issued_ticket`
- un `ticket_scan` appartient à un `user` scanner
- un `ticket_scan` peut appartenir à un `access_gate`
- un `ticket_scan` peut avoir un `scan_incident`

---

# 6.5 Module : formations

## Tables principales

- `trainings`
- `training_sessions`
- `trainers`
- `training_trainers`
- `training_categories`
- `training_tags`
- `training_media`
- `training_enrollments`
- `training_waitlist_entries`
- `attendance_records`
- `training_materials`
- `training_certificates`
- `training_status_histories`

## Relations principales

- une `training` a plusieurs `training_sessions`
- une `training` a plusieurs `trainers` via `training_trainers`
- une `training` a plusieurs `categories` via `training_categories`
- une `training` a plusieurs `tags` via `training_tags`
- une `training` a plusieurs `training_media`
- une `training_session` a plusieurs `training_enrollments`
- une `training_session` a plusieurs `training_waitlist_entries`
- un `training_enrollment` peut avoir plusieurs `attendance_records`
- une `training` a plusieurs `training_materials`
- un `training_enrollment` peut avoir un `training_certificate`
- une `training` a plusieurs `training_status_histories`

---

# 6.6 Module : appels à projets

## Tables principales

- `calls_for_projects`
- `call_lots`
- `call_categories`
- `call_tags`
- `call_form_sections`
- `call_form_fields`
- `applications`
- `application_answers`
- `application_attachments`
- `application_status_histories`
- `reviewers`
- `review_assignments`
- `evaluation_grids`
- `evaluation_criteria`
- `evaluation_scores`
- `review_decisions`

## Relations principales

- un `call_for_projects` a plusieurs `call_lots`
- un `call_for_projects` a plusieurs `categories` via `call_categories`
- un `call_for_projects` a plusieurs `tags` via `call_tags`
- un `call_for_projects` a plusieurs `call_form_sections`
- une `call_form_section` a plusieurs `call_form_fields`
- un `call_for_projects` a plusieurs `applications`
- une `application` a plusieurs `application_answers`
- une `application` a plusieurs `application_attachments`
- une `application` a plusieurs `application_status_histories`
- un `reviewer` a plusieurs `review_assignments`
- une `application` a plusieurs `review_assignments`
- une `evaluation_grid` a plusieurs `evaluation_criteria`
- un `review_assignment` peut produire plusieurs `evaluation_scores`
- une `application` peut avoir plusieurs `review_decisions`

---

# 6.7 Module : crowdfunding

## Tables principales

- `campaigns`
- `campaign_categories`
- `campaign_tags`
- `campaign_media`
- `campaign_milestones`
- `campaign_updates`
- `contributions`
- `contribution_receipts`
- `donor_preferences`
- `campaign_status_histories`

## Relations principales

- une `campaign` a plusieurs `categories` via `campaign_categories`
- une `campaign` a plusieurs `tags` via `campaign_tags`
- une `campaign` a plusieurs `campaign_media`
- une `campaign` a plusieurs `campaign_milestones`
- une `campaign` a plusieurs `campaign_updates`
- une `campaign` a plusieurs `contributions`
- une `contribution` peut appartenir à un `public_user`
- une `contribution` peut avoir un `contribution_receipt`
- une `contribution` peut avoir des préférences via `donor_preferences`
- une `campaign` a plusieurs `campaign_status_histories`

---

# 6.8 Module : stands

## Tables principales

- `salons`
- `salon_categories`
- `salon_tags`
- `halls`
- `zones`
- `stand_types`
- `stands`
- `stand_media`
- `exhibitors`
- `exhibitor_documents`
- `stand_reservations`
- `stand_reservation_items`
- `stand_payment_schedules`
- `salon_status_histories`

## Relations principales

- un `salon` a plusieurs `categories` via `salon_categories`
- un `salon` a plusieurs `tags` via `salon_tags`
- un `salon` a plusieurs `halls`
- un `hall` a plusieurs `zones`
- une `zone` a plusieurs `stands`
- un `stand` appartient à un `stand_type`
- un `stand` a plusieurs `stand_media`
- un `exhibitor` a plusieurs `exhibitor_documents`
- un `exhibitor` a plusieurs `stand_reservations`
- une `stand_reservation` a plusieurs `stand_reservation_items`
- une `stand_reservation` peut avoir plusieurs `stand_payment_schedules`
- un `salon` a plusieurs `salon_status_histories`

---

# 6.9 Module : notifications

## Tables principales

- `notification_templates`
- `notification_campaigns`
- `notifications`
- `notification_logs`
- `notification_preferences`

## Relations principales

- un `notification_template` peut être utilisé par plusieurs `notifications`
- une `notification_campaign` a plusieurs `notifications`
- une `notification` appartient à une entité cible
- un utilisateur public ou local peut avoir des `notification_preferences`
- une `notification` a plusieurs `notification_logs`

---

# 6.10 Module : support local

## Tables principales

- `support_tickets`
- `support_messages`
- `support_attachments`
- `customer_contact_logs`

## Relations principales

- un `support_ticket` appartient à un `public_user`, un client local ou une ressource métier
- un `support_ticket` a plusieurs `support_messages`
- un `support_message` a plusieurs `support_attachments`
- un `support_ticket` peut avoir plusieurs `customer_contact_logs`

---

# 6.11 Module : médias et documents

## Tables principales

- `media_assets`
- `media_folders`
- `media_links`
- `document_records`

## Relations principales

- un `media_folder` a plusieurs `media_assets`
- un `media_asset` peut être lié à plusieurs entités via `media_links`
- un `document_record` appartient à une entité métier

## Remarque

Si tu veux un modèle très strict, tu peux remplacer `media_links` par des tables dédiées par module. Mais pour les médias, un lien polymorphique reste acceptable.

---

# 6.12 Module : audit et historisation locale

## Tables principales

- `audit_logs`
- `status_change_logs`
- `sensitive_action_logs`
- `webhook_logs`

## Relations principales

- un `audit_log` appartient à un `user`
- un `status_change_log` appartient à une entité métier source
- un `sensitive_action_log` appartient à un `user`
- un `webhook_log` peut viser une transaction ou une ressource intégrée

---

# 6.13 Module : reporting et analytics locale

## Tables principales

- `daily_kpis`
- `event_sales_snapshots`
- `training_kpis`
- `campaign_kpis`
- `call_kpis`

## Relations principales

- un snapshot appartient à une ressource métier source
- un agrégat n’est jamais la source de vérité métier

---

# 7. Relations transverses importantes

## 7.1 Tenant vers projections publiques

- un `tenant` a plusieurs `public_events`
- un `tenant` a plusieurs `public_formations`
- un `tenant` a plusieurs `public_calls`
- un `tenant` a plusieurs `public_campaigns`
- un `tenant` a plusieurs `public_salons`
- un `tenant` a un `public_organizer`

## 7.2 Utilisateur public vers modules métier

- un `public_user` a plusieurs `orders`
- un `public_user` a plusieurs `training_enrollments`
- un `public_user` a plusieurs `applications`
- un `public_user` a plusieurs `contributions`
- un `public_user` a plusieurs `support_tickets`

## 7.3 Paiement vers modules métier

- une `payment_transaction` peut viser une `order`
- une `payment_transaction` peut viser une `training_enrollment`
- une `payment_transaction` peut viser une `contribution`
- une `payment_transaction` peut viser une `stand_reservation`

## 7.4 Catégories vers modules métier

- une `category` a plusieurs `events` via `event_categories`
- une `category` a plusieurs `trainings` via `training_categories`
- une `category` a plusieurs `calls_for_projects` via `call_categories`
- une `category` a plusieurs `campaigns` via `campaign_categories`
- une `category` a plusieurs `salons` via `salon_categories`

---

# 8. Tables pivot à prévoir explicitement

Je recommande de prévoir explicitement les pivots suivants.

## Catégories

- `event_categories`
- `training_categories`
- `call_categories`
- `campaign_categories`
- `salon_categories`

## Tags

- `event_tags`
- `training_tags`
- `call_tags`
- `campaign_tags`
- `salon_tags`

## Rôles et permissions

- `platform_user_roles`
- `platform_role_permissions`
- `user_roles`
- `role_permissions`

## Assignations métiers

- `training_trainers`
- `review_assignments`

---

# 9. Relations critiques à sécuriser

Les relations suivantes sont critiques.

## Billetterie

- `events` -> `ticket_types`
- `orders` -> `order_items`
- `attendees` -> `issued_tickets`
- `issued_tickets` -> `ticket_scans`

## Appels à projets

- `calls_for_projects` -> `applications`
- `applications` -> `application_answers`
- `applications` -> `review_assignments`
- `review_assignments` -> `evaluation_scores`

## Crowdfunding

- `campaigns` -> `contributions`

## Stands

- `salons` -> `stands`
- `stand_reservations` -> `stand_reservation_items`

## Paiements

- `payment_attempts` -> `payment_confirmations`
- `payment_transactions` -> `tenant_ledger_entries`
- `platform_transactions` -> `payout_lines`

---

# 10. Recommandation finale

Oui, l’architecture est bonne.

Et pour le passage au modèle de données, je recommande officiellement :

- une vraie table `categories`
- une hiérarchie de catégories avec `parent_id`
- des pivots séparés par module
- des tables `tags` séparées des catégories
- une séparation nette entre tables `central` et tables `tenant`
- des tables d’historique pour les entités critiques
- des tables financières dédiées, distinctes des entités métier

---

# 11. Conclusion

Avec ce document, tu as maintenant une base claire pour passer à l’étape suivante :

- transformer cette liste en **schéma relationnel détaillé**
- choisir les champs exacts table par table
- préparer les futures migrations Laravel
- organiser les modèles Eloquent et les relations Filament

La prochaine suite logique est donc :

- soit un **schéma SQL détaillé table par table**
- soit un **document des champs exacts par table**
- soit une **priorisation des tables à créer en premier**
