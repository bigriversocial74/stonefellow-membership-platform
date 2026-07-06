# Stonefellow Static Website Foundation v1

A PHP static frontend foundation for the Stonefellow rock & roll streaming/music/merch platform.

## Included pages

- `index.php` — Home
- `series.php` — Series overview
- `episodes.php` — Episode library
- `music.php` — Spotify-style music browse / landing page
- `album.php` — Album detail page with track list
- `song.php` — Song / track detail page with lyrics and large controls
- `subscribe.php` — Subscription plans
- `cast.php` — Cast/band page
- `app.php` — Mobile app landing page
- `merch.php` — Merch store landing page

## Shared files

- `includes/config.php` — site settings and URL helpers
- `includes/data.php` — temporary content arrays for episodes, songs, cast, plans, products, and featured cards
- `includes/header.php` — shared header/nav
- `includes/footer.php` — shared footer/newsletter
- `assets/css/stonefellow.css` — full responsive design system
- `assets/js/stonefellow.js` — mobile navigation, preview player controls, save state toggles, next/previous demo controls

## Next phase

Replace the arrays in `includes/data.php` with database queries for:

- episodes
- songs/albums
- cast members
- subscription plans
- products/merch
- homepage sections
- media assets

Then add admin management, login/subscriptions, and cart/checkout.

## Music app notes

The streaming UI is currently static/demo-ready and uses `includes/data.php` as the temporary catalog source. Public playback is wired to `assets/audio/previews/` 30-second WAV previews. The database seed in `database/stonefellow_streaming_platform.sql` includes album, song, song file, playlist, saved-song, play-history, and entitlement foundations for later admin/catalog integration.


Music/player split:
- `music.php` is the restored original/public music page.
- `player.php` contains the newer Spotify-style player/browse interface.

## Build management docs

This package now includes project management and SQL mapping documents for the full membership build:

- `docs/STONEFELLOW_MEMBERSHIP_BUILD_OUTLINE.md`
- `docs/SQL_FILE_MAP.md`
- `database/migrations/001_membership_video_tracking.sql`

For a brand-new database, import `database/stonefellow_streaming_platform.sql` first, then apply migration files in numeric order from `database/migrations/`.


## Membership build phase added

This package now includes the first operational membership foundation:

- `episode.php` full episode detail page
- `watch.php` full video player page with lock/resume/tracking UI
- `member.php` member dashboard
- `playlists.php` private playlist page
- `admin/index.php` build-control admin foundation
- `api/audio-track.php`, `api/video-track.php`, `api/playlist.php`, and `api/membership-status.php`
- `includes/db.php` and `includes/membership.php`
- `docs/STONEFELLOW_BUILD_STATUS.md`

Database persistence is optional until deployment credentials are configured with `SF_DB_HOST`, `SF_DB_NAME`, `SF_DB_USER`, and `SF_DB_PASS`.


- `database/migrations/002_video_playlist_runtime_seed.sql` — aligns episode/video seed records and expected video file paths with `episode.php` and `watch.php`.


## Admin Media Catalog Manager v1

The package now includes a first admin manager for the Stonefellow membership site.

Start here:

- `admin/index.php`
- `admin/music.php`

Admin sections include albums, songs/audio files, episodes, videos/video files, membership access rules, and media assets. See `docs/ADMIN_MEDIA_CATALOG_MANAGER_V1.md` for details.

## Member Auth + Access Gates v1

This package adds database-backed member signup/signin/logout, password reset tokens, account dashboard, subscriber playlist gates, admin member management, and sandbox subscription activation for testing. See `docs/MEMBER_AUTH_ACCESS_GATES_V1.md`.

## Media Upload + Storage v1

This package adds real media upload handling through `admin/uploads.php`.

New/updated pieces:

- upload images, audio, video, and documents into local `assets/*/uploads/YYYY/MM/` folders
- preview uploaded images/audio/video in the admin asset library
- register CDN/manual paths without uploading
- attach uploaded image assets to albums/songs/videos
- choose uploaded audio assets for song file variants
- choose uploaded video assets for video file variants
- optional metadata migration: `database/migrations/003_media_upload_storage_metadata.sql`

Recommended SQL import order is now base SQL, then migrations `001`, `002`, and `003` in order.

See `docs/MEDIA_UPLOAD_STORAGE_V1.md` for details.

## Analytics Dashboard v1

This package adds the first admin analytics section:

- `admin/analytics.php`
- `admin/audio-analytics.php`
- `admin/video-analytics.php`
- `admin/member-activity.php`
- `includes/admin_analytics.php`
- `docs/ANALYTICS_DASHBOARD_V1.md`

Analytics v1 reads existing tracking, member, playlist, catalog, and order tables. No new migration is required beyond the existing base SQL plus migrations `001`, `002`, and `003`.


## Billing + Subscriptions v1

This package now includes a sandbox-ready membership checkout system:

- `billing-checkout.php`
- `billing-success.php`
- `billing-cancel.php`
- `account-billing.php`
- `admin/billing.php`
- `api/billing-webhook.php`
- `includes/billing.php`
- `database/migrations/004_billing_entitlements.sql`
- `docs/BILLING_SUBSCRIPTIONS_V1.md`

Run migration `004_billing_entitlements.sql` after the base SQL and migrations 001-003 to enable checkout sessions, invoices, payment transactions, webhook logs, and subscription entitlement activation.


## Merch Cart + Order Runtime v1

Added operational merch cart/order runtime. See `docs/MERCH_CART_ORDER_RUNTIME_V1.md` and run `database/migrations/005_merch_order_runtime.sql` after migrations 001-004 for database-backed order audit columns, status history, and inventory movement logs.


## Merch Runtime Code Audit

Scoped audit completed and documented in `docs/CODE_AUDIT_MERCH_CART_ORDER_RUNTIME_V1.md`. Final scoped section score: 10/10 after fixes for static data loading, order inventory reversal, API CSRF validation, and session checkout smoke testing.


## Email + Notification Runtime v1

Added `includes/notifications.php`, `admin/notifications.php`, `admin/email-templates.php`, `api/notification-webhook.php`, and `database/migrations/006_email_notifications.sql`. This layer logs/sends transactional emails for signup, password reset, subscriptions, payment receipts, merch orders, fulfillment, and admin alerts. Default provider is safe `log` mode.


Audit: `docs/CODE_AUDIT_EMAIL_NOTIFICATION_RUNTIME_V1.md`

## Next Stages Combined Build

This package now includes the next operational stages after Email + Notification Runtime v1:

1. **Site Settings + Installer v1**
   - `install.php`
   - `admin/settings.php`
   - `admin/system-health.php`
   - `includes/settings.php`
   - `database/migrations/007_site_settings_installer.sql`

2. **Payment Gateway Adapter v1**
   - `includes/payment_gateway.php`
   - `admin/payment-gateways.php`
   - `api/payment-webhook.php`
   - `database/migrations/008_payment_gateway_adapter.sql`

3. **Episode/Video Admin Upgrade v2**
   - `admin/seasons.php`
   - upgraded `admin/episodes.php`
   - `admin/release-schedule.php`
   - `database/migrations/009_episode_video_admin_v2.sql`

4. **Frontend QA + Mobile Polish v1**
   - responsive admin/public layout improvements in `assets/css/stonefellow.css`

Run the base SQL first, then migrations `001` through `009` in order for full database mode.

Combined phase audit: see `docs/CODE_AUDIT_NEXT_STAGES_COMBINED.md`.

## Production Readiness + QA Harness v1

Added launch readiness tools:

- `admin/qa.php` — main production readiness dashboard
- `admin/migration-checker.php` — base SQL + migrations 001–010 table/column checks
- `admin/routes-checker.php` — public/admin/API route matrix
- `admin/security-check.php` — admin gates, CSRF, auth, uploads, webhooks, PDO checks
- `admin/content-audit.php` — missing media/asset reference audit
- `includes/qa.php` — shared QA helper layer
- `database/migrations/010_production_readiness_qa_harness.sql` — optional persisted QA run history

Docs:

- `docs/PRODUCTION_READINESS_QA_HARNESS_V1.md`
- `docs/DEPLOYMENT_RUNBOOK.md`
- `docs/CODE_AUDIT_PRODUCTION_READINESS_QA_HARNESS_V1.md`

Run the base SQL first, then migrations `001` through `010` in order for full database-backed launch readiness tracking.

Scoped phase audit score: **10/10**. The live readiness dashboard can still show review items until production environment variables, SQL migrations, writable upload folders, payment secrets, email settings, and real media assets are configured.
