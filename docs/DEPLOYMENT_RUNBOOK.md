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

## 3. SQL installer order

The installer runs:

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

Migration `013` includes publishing, library, and search discovery tables.

## 4. Environment variables

Use `.env.example` as the production template. Do not commit real secrets.

Minimum:

```bash
SF_DB_HOST=localhost
SF_DB_NAME=stonefellow
SF_DB_USER=stonefellow_user
SF_DB_PASS=strong-password
SF_HASH_SALT=long-random-secret
SF_MEDIA_SIGNING_KEY=long-random-media-key
```

Payment keys are only required when moving beyond sandbox mode.

## 5. Admin checks

Open:

- `admin/system-health.php`
- `admin/qa.php`
- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`
- `admin/streaming-analytics.php`

Also open:

```txt
deploy/preflight.php
```

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

## 7. Smoke tests

Test:

- signup/signin/reset password
- installer lock behavior
- subscribe checkout
- member dashboard
- library and watchlist
- search
- music player preview and full access
- episode/watch page playback
- merch cart/checkout/order confirmation
- admin fulfillment
- email notification log
- payment webhook test
- analytics summary API

## 8. Rollback plan

Before deployment:

- keep a copy of the previous webroot
- export the current database
- keep the prior ZIP package
- record the active migration number

Rollback:

1. restore previous webroot
2. restore prior database backup when schema/data changed
3. re-test signin, admin, checkout, and watch/player pages

## 9. Final launch gate

Launch when:

- installer completes
- QA has no failed checks
- system health has no critical failures
- content audit has no required missing live assets
- payment webhooks test successfully
- email notifications work
- full subscriber media files are protected
- admin account access is verified
