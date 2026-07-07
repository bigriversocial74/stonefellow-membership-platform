# Stonefellow Deployment Runbook

## 1. Prepare hosting

Required production stack:

- PHP 8.1+
- PDO + PDO MySQL
- JSON extension
- Fileinfo extension recommended
- HTTPS enabled
- MySQL/MariaDB database
- writable upload folders

Writable folders:

- `config/`
- `storage/`
- `assets/images/uploads/`
- `assets/audio/uploads/`
- `assets/video/uploads/`
- `assets/documents/uploads/`

## 2. Fresh install

1. Download the latest `main` ZIP.
2. Upload and extract into the hosting web root.
3. Visit the public URL.
4. Open `install.php`.
5. Confirm server checks.
6. Enter database credentials.
7. Run the SQL installer step.
8. Create the admin account.
9. Confirm the installer writes `config/local.php` and `storage/install.lock`.
10. Sign in and open `admin/index.php`.

## 3. SQL installer order

The installer runs the base schema and migrations in this order:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`
4. `database/migrations/003_media_upload_storage_metadata.sql`
5. `database/migrations/004_billing_entitlements.sql`
6. `database/migrations/005_merch_order_runtime.sql`
7. `database/migrations/006_email_notifications.sql`
8. `database/migrations/007_site_settings_installer.sql`
9. `database/migrations/008_payment_gateway_adapter.sql`
10. `database/migrations/009_episode_video_admin_v2.sql`
11. `database/migrations/010_production_readiness_qa_harness.sql`
12. `database/migrations/011_content_import_seed_manager.sql`
13. `database/migrations/012_audio_player_entitlements_v2.sql`
14. `database/migrations/013_gateway_publishing_workflow_v1.sql`
15. `database/migrations/014_feed_personalization_engagement_analytics.sql`
16. `database/migrations/015_membership_tiers_revenue_dashboard.sql`
17. `database/migrations/016_member_lifecycle_support_helpdesk.sql`
18. `database/migrations/017_ops_scheduler_member_messaging.sql`
19. `database/migrations/018_admin_roles_security_audit.sql`
20. `database/migrations/019_backup_release_manager.sql`
21. `database/migrations/020_monitoring_incident_alerts.sql`

Existing installs should apply only missing migrations in order. Migration `020` must run after migration `019`.

## 4. Environment configuration

Use `.env.example` as the production template. Do not commit real credentials, salts, signing keys, payment keys, or webhook secrets.

Minimum required categories:

- database host/name/user/password
- hash salt
- media signing key
- payment keys before live payment mode
- webhook secrets before live payment mode
- mail delivery credentials before live email sending

## 5. Admin checks

Open these launch-control pages:

- `admin/index.php`
- `admin/launch-checklist.php`
- `admin/qa.php`
- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`
- `admin/system-health.php`

Open these production-ops pages:

- `admin/monitoring.php`
- `admin/incidents.php`
- `admin/backups.php`
- `admin/releases.php`
- `admin/ops-scheduler.php`
- `admin/member-messaging.php`
- `admin/member-lifecycle.php`
- `admin/support.php`

Open these reporting and revenue pages:

- `admin/streaming-analytics.php`
- `admin/engagement-analytics.php`
- `admin/revenue-dashboard.php`
- `admin/security-dashboard.php`
- `admin/roles.php`

Also run `deploy/preflight.php` before public launch.

## 6. Configure production

In admin, configure:

- site settings
- support/admin emails
- payment provider
- payment gateway keys and webhooks
- email templates
- products and inventory
- music, episodes, videos, and media files
- publishing schedule
- member access and entitlements
- membership tiers and public plan packaging
- role assignments and admin permissions
- scheduler jobs
- backup records and release records
- monitoring thresholds and incident alert rules

## 7. Smoke tests

Test:

- signup, signin, logout, forgot password, reset password
- installer lock behavior
- subscribe checkout, billing success, and billing cancel flows
- member dashboard
- member notifications, messages, comments, and support center
- library and watchlist
- search
- music player preview and full access
- episode/watch page playback
- signed stream/download access
- merch cart, checkout, and order confirmation
- admin media/catalog management
- member messaging campaign send path
- ops scheduler manual run path
- email notification log
- payment webhook test
- backup readiness API
- release manager API
- monitoring API
- incidents API
- analytics summary API

## 8. Backup and release gate

Before deployment:

1. Create a backup record in `admin/backups.php`.
2. Export the database.
3. Preserve uploads, config, docs, and logs according to the backup profile.
4. Create a release record in `admin/releases.php`.
5. Record active branch, commit SHA, migration range, deploy notes, and rollback notes.
6. Pass or waive release checklist tasks intentionally.
7. Run `deploy/preflight.php`.

## 9. Rollback plan

Before deployment:

- keep a copy of the previous webroot
- export the current database
- keep the prior ZIP package
- record the active migration number
- document the rollback trigger and owner

Rollback:

1. restore previous webroot
2. restore prior database backup when schema/data changed
3. re-test signin, admin, checkout, watch/player pages, monitoring, and incidents
4. add a release event explaining the rollback

## 10. Final launch gate

Launch when:

- installer completes
- migrations are applied through `020`
- QA has no failed checks
- route registry has no missing required routes
- system health has no critical failures
- monitoring has no unresolved critical errors
- incidents have no unresolved launch-blocking records
- content audit has no required missing live assets
- payment webhooks test successfully
- email notifications work
- member messages and support workflows load
- full subscriber media files are protected
- backup and release records exist for the deploy
- admin account access and role permissions are verified
