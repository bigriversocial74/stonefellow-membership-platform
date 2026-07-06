# Stonefellow Build Status

## Current package phase

This package now contains the first operational membership foundation for the Stonefellow static/PHP site.

## Pages added

- `episode.php` — full episode detail page with trailer/full-video CTAs and access messaging.
- `watch.php` — full video watch page with membership lock overlay, video element, resume card, and tracking hooks.
- `member.php` — member dashboard for access status, continue watching, audio tracking, and playlists.
- `playlists.php` — private member playlist page with create form and song add buttons.
- `admin/index.php` — admin build-control landing page for tracking the content/membership build.

## APIs added

- `api/audio-track.php` — accepts audio play, pause, seek, progress, complete, skip, replay, and error events.
- `api/video-track.php` — accepts video play, pause, seek, progress, complete, rewatch, and error events.
- `api/playlist.php` — supports private playlist creation and song adds for signed-in member sessions.
- `api/membership-status.php` — returns current access snapshot.

## Includes added

- `includes/db.php` — optional PDO connection using `SF_DB_HOST`, `SF_DB_NAME`, `SF_DB_USER`, `SF_DB_PASS`, and `SF_DB_CHARSET`.
- `includes/membership.php` — access-level helpers and current member snapshot.

## Current behavior

Without database environment variables, tracking endpoints accept payloads and return `stored:false` so the front end can be tested safely. With database variables configured and SQL imported, the same endpoints persist events and update progress tables.

## Next backend steps

1. Connect real sign-in/signup sessions to `$_SESSION['sf_user_id']`.
2. Wire payment provider webhooks to `user_subscriptions`.
3. Build admin CRUD for albums, songs, episodes, videos, and product drops.
4. Add secure video/audio file serving instead of direct static file paths.
5. Add analytics reports for plays, completions, watch time, subscriber retention, and episode/music conversion.


- `database/migrations/002_video_playlist_runtime_seed.sql` — aligns episode/video seed records and expected video file paths with `episode.php` and `watch.php`.


## Admin Media Catalog Manager v1 — complete

Added a full admin catalog foundation for albums, songs, episodes, videos, assets, membership plans, and direct content grants.

Files added/updated:

- `includes/admin_catalog.php`
- `admin/index.php`
- `admin/music.php`
- `admin/music-albums.php`
- `admin/music-songs.php`
- `admin/episodes.php`
- `admin/videos.php`
- `admin/media-access.php`
- `admin/uploads.php`
- `docs/ADMIN_MEDIA_CATALOG_MANAGER_V1.md`

The admin manager supports database-backed CRUD when MySQL is configured and falls back to static/demo previews when no DB is available.

## Member Auth + Access Gates v1

Completed:
- Database-backed signup, signin, logout, remember-me, and password reset foundation.
- Member account dashboard.
- Admin members/subscriptions page.
- Subscriber gate for private playlists.
- Sandbox/manual subscription activation for testing before payment gateway integration.
- Music player source selection now respects member access and full-file availability.

Next recommended phase: payment checkout/webhook integration or analytics dashboards.

## Media Upload + Storage v1

Completed:

- Real admin file upload handling in `admin/uploads.php`.
- Upload validation by media type, file extension, MIME type, and size.
- Local storage folders for image, audio, video, and document uploads.
- Asset preview UI for image/audio/video/document assets.
- Catalog picker wiring for album covers, song covers, song audio files, video posters, and video stream/trailer files.
- New migration `database/migrations/003_media_upload_storage_metadata.sql` for optional upload metadata.
- Backward-compatible media asset inserts/updates that work even before migration 003 is applied.

Next recommended phase: build admin analytics dashboards for audio plays, video watch time, episode completion, member playlist activity, and conversion signals.

## Analytics Dashboard v1

Completed:

- `admin/analytics.php` — platform overview for audio, video, members, playlists, subscriptions, and merch revenue.
- `admin/audio-analytics.php` — song performance and recent audio event report.
- `admin/video-analytics.php` — video/episode performance and recent watch event report.
- `admin/member-activity.php` — member engagement table across audio, video, playlists, subscriptions, and logins.
- `includes/admin_analytics.php` — shared analytics helper layer.
- Admin navigation updated with Analytics, Audio Report, Video Report, and Activity sections.

No new SQL migration is required. Analytics v1 reads the tracking tables already introduced by `database/migrations/001_membership_video_tracking.sql`, plus existing member, playlist, and order tables.

Next recommended phase: build Payment Checkout + Webhook v1 so subscriptions move from sandbox/manual activation to real paid membership lifecycle.


## Billing + Subscriptions v1

Complete in this package. Membership plan selection now starts a checkout session, sandbox payment completion creates active subscriptions, invoices, payment transactions, and subscription access grants. Members can review/cancel from `account-billing.php`; admins can monitor checkouts, subscriptions, invoices, transactions, and webhooks from `admin/billing.php`.


## Merch Cart + Order Runtime v1

Completed: database/session cart runtime, public product/detail/cart/checkout/receipt pages, admin merch product/inventory management, admin order queue, sandbox merch payment transaction records, order status history, and inventory movement logging. See `docs/MERCH_CART_ORDER_RUNTIME_V1.md`.


## Merch Runtime Code Audit

Scoped audit completed and documented in `docs/CODE_AUDIT_MERCH_CART_ORDER_RUNTIME_V1.md`. Final scoped section score: 10/10 after fixes for static data loading, order inventory reversal, API CSRF validation, and session checkout smoke testing.


## Email + Notification Runtime v1

Status: Built.

Added transactional email templates, notification log/queue, admin notification dashboard, template editor, webhook receiver, and integration with signup, password reset, billing, subscription cancel, merch order confirmation, order fulfillment, and admin order alerts. Requires `database/migrations/006_email_notifications.sql` for database-backed operation.


Audit: `docs/CODE_AUDIT_EMAIL_NOTIFICATION_RUNTIME_V1.md`

## Next Stages Combined Build

Completed after Email + Notification Runtime v1:

- Site Settings + Installer v1
- Payment Gateway Adapter v1
- Episode/Video Admin Upgrade v2
- Frontend QA + Mobile Polish v1

New pages:

- `install.php`
- `admin/settings.php`
- `admin/system-health.php`
- `admin/payment-gateways.php`
- `admin/seasons.php`
- `admin/release-schedule.php`
- `api/payment-webhook.php`

New includes:

- `includes/settings.php`
- `includes/payment_gateway.php`

New migrations:

- `database/migrations/007_site_settings_installer.sql`
- `database/migrations/008_payment_gateway_adapter.sql`
- `database/migrations/009_episode_video_admin_v2.sql`

Audit/scoring docs:

- `docs/SITE_SETTINGS_INSTALLER_V1.md`
- `docs/PAYMENT_GATEWAY_ADAPTER_V1.md`
- `docs/EPISODE_VIDEO_ADMIN_V2.md`
- `docs/FRONTEND_QA_MOBILE_POLISH_V1.md`
- `docs/CODE_AUDIT_NEXT_STAGES_COMBINED.md`

Final scoped score for this combined phase: **10/10**.

## Production Readiness + QA Harness v1

Status: Built.

Added:

- `includes/qa.php`
- `admin/qa.php`
- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`
- `database/migrations/010_production_readiness_qa_harness.sql`
- `docs/PRODUCTION_READINESS_QA_HARNESS_V1.md`
- `docs/DEPLOYMENT_RUNBOOK.md`
- `docs/CODE_AUDIT_PRODUCTION_READINESS_QA_HARNESS_V1.md`

Also updated:

- admin navigation
- admin dashboard
- central admin POST CSRF guard
- SQL map
- README
- CSS for QA/admin report tables

Scoped audit score: **10/10**.

Live environment readiness may still show review items until database credentials, migrations, upload permissions, media assets, payment secrets, and mail settings are configured on the production host.

## Content Import + Seed Manager v1

Status: Built and merged.

Added:

- `includes/importer.php`
- `admin/import.php`
- `admin/seed-manager.php`
- `admin/demo-content.php`
- `database/migrations/011_content_import_seed_manager.sql`
- `database/seeds/starter_catalog.json`
- `docs/CONTENT_IMPORT_SEED_MANAGER_V1.md`
- `docs/CODE_AUDIT_CONTENT_IMPORT_SEED_MANAGER_V1.md`

The import manager supports CSV/JSON preview, normalized payload validation, natural-key upserts, import batch logs, row-level audit history, starter seed content, and rollback support.

Scoped audit score: **10/10**.

## Web Installer + Launch Wizard v1

Status: Built in this phase.

Added/updated:

- `includes/installer.php`
- `install.php`
- `includes/config.php`
- `includes/db.php`
- `includes/settings.php`
- `config/README.md`
- `config/.htaccess`
- `storage/README.md`
- `storage/.htaccess`
- `.gitignore`
- `docs/WEB_INSTALLER_LAUNCH_WIZARD_V1.md`
- `docs/CODE_AUDIT_WEB_INSTALLER_LAUNCH_WIZARD_V1.md`

The installer supports server checks, database connection confirmation, automatic SQL import for the base schema and migrations 001 through 011, migration checksum tracking, first admin account creation, site setting save, local config writing, install lock creation, and automatic uninstalled-app redirect to `install.php`.

Scoped audit score: **10/10**.
