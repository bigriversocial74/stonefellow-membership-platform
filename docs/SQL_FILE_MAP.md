# Stonefellow SQL File Map

This file maps the current Stonefellow database foundation, installer order, migration purpose, and operational SQL rules for production handoff.

## Current install order

For a brand-new install, run the installer or import the files in this exact order:

1. `database/stonefellow_streaming_platform.sql` — base streaming platform schema
2. `database/migrations/001_membership_video_tracking.sql` — membership video tracking, grants, playback progress, and admin audit foundation
3. `database/migrations/002_video_playlist_runtime_seed.sql` — video and playlist runtime seed data
4. `database/migrations/003_media_upload_storage_metadata.sql` — upload metadata for media assets
5. `database/migrations/004_billing_entitlements.sql` — billing, checkout, invoices, transactions, and webhooks
6. `database/migrations/005_merch_order_runtime.sql` — order status history and inventory movements
7. `database/migrations/006_email_notifications.sql` — templates, notification queue/log, preferences, and notification webhooks
8. `database/migrations/007_site_settings_installer.sql` — site settings and installation checks
9. `database/migrations/008_payment_gateway_adapter.sql` — payment gateway settings and gateway webhook events
10. `database/migrations/009_episode_video_admin_v2.sql` — seasons and video chapters
11. `database/migrations/010_production_readiness_qa_harness.sql` — persisted QA runs and QA check results
12. `database/migrations/011_content_import_seed_manager.sql` — content import batches and import rows
13. `database/migrations/012_audio_player_entitlements_v2.sql` — user player state
14. `database/migrations/013_gateway_publishing_workflow_v1.sql` — publishing workflow, member library, search, activity, notifications, comments, and creator posts
15. `database/migrations/014_feed_personalization_engagement_analytics.sql` — follows, feed preferences, personalized feed items, engagement analytics, and member engagement scores
16. `database/migrations/015_membership_tiers_revenue_dashboard.sql` — membership tier packaging, tier benefit matrix, revenue snapshots, and checkout conversion events
17. `database/migrations/016_member_lifecycle_support_helpdesk.sql` — lifecycle notes, retention tasks, support tickets, ticket messages, and support events
18. `database/migrations/017_ops_scheduler_member_messaging.sql` — ops scheduler, job runs, member message threads, member messages, campaigns, and campaign recipients
19. `database/migrations/018_admin_roles_security_audit.sql` — admin roles, permissions, role mapping, admin role assignment, security audit events, and admin security sessions
20. `database/migrations/019_backup_release_manager.sql` — backup profiles, backup runs, restore checks, deployment releases, release tasks, and deployment events
21. `database/migrations/020_monitoring_incident_alerts.sql` — monitoring snapshots, error events, service checks, incidents, incident events, alert rules, and admin alert notifications

## Production operating rule

For a fresh install, use `install.php` whenever possible. The installer runs the base schema plus migrations `001` through `020` and records applied migration checksums.

For an existing install:

1. Export a database backup first.
2. Confirm the current migration number in `schema_migrations` or the hosting SQL history.
3. Apply only missing migrations in numeric order.
4. Do not re-import the base SQL over live data.
5. Run `admin/migration-checker.php` after applying migrations.
6. Run `admin/routes-checker.php`, `admin/security-check.php`, `admin/qa.php`, and `deploy/preflight.php` before launch.

## Core table groups

### Base schema

The base schema creates the initial platform foundation: `media_assets`, `users`, `user_auth_tokens`, `login_attempts`, `subscription_plans`, `user_subscriptions`, `streaming_entitlements`, `albums`, `songs`, `song_files`, `song_episode_links`, `user_saved_songs`, `user_play_history`, `episodes`, `playlists`, `playlist_songs`, `product_categories`, `products`, `product_images`, `product_variants`, `carts`, `cart_items`, `orders`, and `order_items`.

### Membership, playback, and video tracking

Migrations `001` through `003` add `content_access_grants`, `videos`, `video_files`, `audio_play_events`, `user_song_progress`, `video_watch_events`, `user_video_progress`, `user_episode_progress`, `admin_audit_log`, and media upload metadata columns.

### Billing, orders, notifications, and settings

Migrations `004` through `008` add `billing_customers`, `subscription_checkouts`, `invoices`, `payment_transactions`, `billing_webhook_events`, `order_status_history`, `product_inventory_movements`, `email_templates`, `notification_logs`, `notification_preferences`, `notification_webhook_events`, `site_settings`, `system_installation_checks`, `payment_gateway_settings`, and `payment_gateway_webhook_events`.

### Content operations and discovery

Migrations `009` through `014` add `seasons`, `video_chapters`, `qa_runs`, `qa_check_results`, `content_import_batches`, `content_import_rows`, `user_player_state`, `publishing_events`, `content_release_rules`, `member_library_items`, `content_search_index`, `member_activity_events`, `content_ops_tasks`, `member_notifications`, `fan_comments`, `fan_reactions`, `comment_moderation_events`, `creator_posts`, `creator_post_media`, `member_follows`, `member_feed_preferences`, `member_feed_items`, `engagement_analytics_daily`, and `member_engagement_scores`.

### Membership packaging, lifecycle, scheduler, and messaging

Migrations `015` through `017` add `membership_tier_benefits`, `membership_tier_benefit_map`, `launch_revenue_snapshots`, `checkout_conversion_events`, `member_lifecycle_notes`, `member_retention_tasks`, `support_tickets`, `support_ticket_messages`, `support_ticket_events`, `ops_scheduled_jobs`, `ops_job_runs`, `member_message_threads`, `member_messages`, `member_message_campaigns`, and `member_message_recipients`.

### Admin security, backup/release, monitoring, and incidents

Migrations `018` through `020` add `admin_roles`, `admin_permissions`, `admin_role_permissions`, `admin_user_roles`, `security_audit_events`, `admin_security_sessions`, `backup_profiles`, `backup_runs`, `restore_readiness_checks`, `deployment_releases`, `deployment_release_tasks`, `deployment_events`, `monitoring_health_snapshots`, `monitoring_error_events`, `monitoring_service_checks`, `incident_records`, `incident_events`, `alert_rules`, and `admin_alert_notifications`.

## Runtime files that verify the SQL layer

Use these admin tools after SQL import:

- `admin/migration-checker.php` — verifies migration files, expected tables, and core column contracts
- `admin/routes-checker.php` — verifies public, member, admin, API, media, and deploy utility routes
- `admin/qa.php` — verifies environment, migrations, routes, security, and content checks
- `admin/system-health.php` — verifies runtime system health
- `admin/monitoring.php` — verifies monitoring/error center runtime records
- `admin/incidents.php` — verifies incident and alert workflow records
- `admin/backups.php` — verifies backup/restore readiness records
- `admin/releases.php` — verifies release manager and deployment checklist records
- `deploy/preflight.php` — performs browser/CLI deployment preflight checks
