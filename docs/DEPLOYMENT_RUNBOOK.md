# Stonefellow Deployment Runbook

## 1. Prepare hosting

Required production settings:

- PHP 8.1+
- PDO and PDO MySQL
- JSON extension
- Fileinfo extension recommended for upload verification
- HTTPS enabled
- MySQL/MariaDB database
- writable upload folders

Writable folders:

- `assets/images/uploads/`
- `assets/audio/uploads/`
- `assets/video/uploads/`
- `assets/documents/uploads/`

## 2. Configure environment variables

Minimum database variables:

```bash
SF_DB_HOST=localhost
SF_DB_NAME=stonefellow
SF_DB_USER=stonefellow_user
SF_DB_PASS=strong-password
SF_DB_CHARSET=utf8mb4
```

Recommended production variables:

```bash
SF_HASH_SALT=long-random-secret
SF_ADMIN_EMAIL=admin@example.com
SF_PAYMENT_PROVIDER=sandbox
SF_STONEFELLOW_WEBHOOK_SECRET=long-random-webhook-secret
```

When moving from sandbox to real payments, configure processor keys and webhook secrets for the selected adapter.

## 3. Import SQL

Run in order:

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

## 4. Create the first admin

Open `signup.php` and create the first user. The auth layer promotes the first registered user to admin when no users exist.

Then open:

- `admin/index.php`
- `admin/settings.php`
- `admin/system-health.php`
- `admin/qa.php`

## 5. Run launch checks

Use the admin QA pages:

- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`
- `admin/qa.php`

Resolve all failed items before launch. Warnings in static/demo media paths are acceptable only before real catalog content has been uploaded.

## 6. Configure core site settings

Open `admin/settings.php` and set:

- site name
- tagline
- support email
- admin alert email
- base URL if needed
- checkout enabled
- member signup enabled
- payment provider

## 7. Configure payments

Open `admin/payment-gateways.php`.

For sandbox launch, keep `sandbox` active. For real payments, configure Stripe/PayPal credentials and test webhooks before switching public checkout to live processing.

## 8. Configure email

Open:

- `admin/email-templates.php`
- `admin/notifications.php`

Verify welcome, password reset, receipt, order confirmation, fulfillment, and admin alert templates.

## 9. Upload content

Open `admin/uploads.php`, then wire assets through:

- `admin/music-albums.php`
- `admin/music-songs.php`
- `admin/seasons.php`
- `admin/episodes.php`
- `admin/videos.php`
- `admin/products.php`

Re-run `admin/content-audit.php` after upload.

## 10. Smoke-test public flows

Test these flows manually:

- signup
- signin
- forgot/reset password
- subscribe checkout
- member dashboard
- music player preview and full access
- playlist creation for paid member
- episode detail
- watch page resume tracking
- merch product detail
- cart add/update/remove
- checkout
- order confirmation
- admin order fulfillment
- email notification log

## 11. Rollback plan

Before deployment:

- keep a copy of the previous webroot
- export the current database
- keep the prior ZIP package
- record the active migration number

If rollback is needed:

1. restore previous webroot
2. restore prior database backup if schema/data changed
3. re-test signin, admin, checkout, and watch/player pages

## 12. Final launch gate

The launch gate is passed when:

- PHP syntax checks pass
- QA harness has no failed checks
- System Health has no critical failures
- migration checker confirms tables and required columns
- content audit has no missing required live assets
- payment webhooks test successfully
- email notifications log/send successfully
- admin account access is verified
