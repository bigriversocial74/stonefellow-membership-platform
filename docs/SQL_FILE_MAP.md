# Stonefellow SQL File Map

This file maps the current SQL foundation and the next migration files for the Stonefellow script.

## Current SQL files

### `database/stonefellow_streaming_platform.sql`

Current role: base install file for the static-to-database platform foundation.

Approximate size: 435 lines.

This file currently includes table groups for:

1. Media assets
2. Users and auth foundation
3. Subscription plans and user subscriptions
4. Music catalog
5. Episode catalog
6. Playlist foundation
7. Audio play history foundation
8. Streaming entitlements
9. Ecommerce/merch catalog
10. Cart and order foundation

## Existing table map

### Media

| Table | Purpose | Notes |
|---|---|---|
| `media_assets` | Shared image/audio/video/document asset registry. | Used by album covers, product images, and future posters/files. |

### Users/auth

| Table | Purpose | Notes |
|---|---|---|
| `users` | Member/admin accounts. | Has email, password hash, display name, role, status. Later altered with email verification and last login fields. |
| `user_auth_tokens` | Remember-me, email verification, and password reset tokens. | Supports auth flows. |
| `login_attempts` | Login success/failure audit trail. | Useful for lockout/rate limiting. |

### Membership/subscriptions

| Table | Purpose | Notes |
|---|---|---|
| `subscription_plans` | Membership plan definitions. | Existing columns cover music/offline flags. Needs video/playlist flags added by migration. |
| `user_subscriptions` | User plan subscriptions and billing status. | Supports active/trial/past_due/canceled/expired states. |
| `streaming_entitlements` | User-level streaming grants. | Existing types cover full music, downloads, premium music, and live sessions. Needs video/founding fan types added by migration. |

### Music catalog

| Table | Purpose | Notes |
|---|---|---|
| `albums` | Album metadata. | Current seed has `The Road Is Calling`. |
| `songs` | Song metadata. | Current seed has 10 Stonefellow songs. |
| `song_files` | Preview/full/live/demo/acoustic audio file records. | Current seed points to preview and full file paths. |
| `song_episode_links` | Connects songs to episodes/scenes. | Good for soundtrack-by-episode pages. |
| `user_saved_songs` | User song saves/favorites. | Good for member library. |
| `user_play_history` | Summary play history row. | Good start, but detailed tracking needs `audio_play_events` and `user_song_progress`. |

### Episodes

| Table | Purpose | Notes |
|---|---|---|
| `episodes` | Episode metadata. | Current table has season, episode number, title, slug, description, runtime, status. It does not yet store playable video files or progress. |

### Playlists

| Table | Purpose | Notes |
|---|---|---|
| `playlists` | User/system playlists. | Supports user-owned, private/public/system playlists. Paying-member enforcement should happen in entitlement logic. |
| `playlist_songs` | Song rows inside playlists. | Supports ordering through `sort_order`. |

### Ecommerce/merch

| Table | Purpose | Notes |
|---|---|---|
| `product_categories` | Merch category list. | Seeded with Apparel, Music, Posters, Accessories, Bundles. |
| `products` | Merch/product catalog. | Supports physical, digital, bundle, access level, featured/limited status. |
| `product_images` | Additional product images. | Maps products to media assets. |
| `product_variants` | Size/color/variant inventory. | Needed for apparel and collectibles. |
| `carts` | Active/converted/abandoned shopping carts. | User or session based. |
| `cart_items` | Cart line items. | Product/variant/quantity/unit price. |
| `orders` | Order headers. | Stores totals, shipping fields, payment id, status. |
| `order_items` | Order line items. | Snapshot of product/variant names and pricing. |

## Gaps in the current base SQL

The current SQL foundation is good, but a fully operational membership site still needs:

- Dedicated video catalog tables.
- Dedicated video file variants.
- Episode watch progress.
- Fine-grained audio play events.
- User song resume/progress records.
- User video resume/progress records.
- Generalized content grants for one-off/manual access.
- Seed rows for subscription plans.
- Admin audit logging.

## New migration added in this package

### `database/migrations/001_membership_video_tracking.sql`

Purpose: add the missing operational tables for membership, audio analytics, video access, and episode tracking without rewriting the base SQL file.

Adds/updates:

- Adds plan feature columns to `subscription_plans`.
- Expands `streaming_entitlements` for video/founding fan access.
- Seeds Monthly, Annual, and Founding Fan plans.
- Adds `content_access_grants`.
- Adds `videos`.
- Adds `video_files`.
- Adds `audio_play_events`.
- Adds `user_song_progress`.
- Adds `video_watch_events`.
- Adds `user_video_progress`.
- Adds `user_episode_progress`.
- Adds `admin_audit_log`.

## Recommended SQL operating rule

For a brand-new install:

1. Import `database/stonefellow_streaming_platform.sql`.
2. Import each file in `database/migrations/` in numeric order.

For an existing install:

1. Back up the database.
2. Apply only new migration files that have not already been run.
3. Do not re-import the base SQL over live data.

## Future migration sequence

Recommended next migration files:

- `002_auth_sessions_and_security.sql`
- `003_admin_catalog_management.sql`
- `004_payment_provider_fields.sql`
- `005_reporting_rollups.sql`
- `006_content_delivery_protection.sql`


## Runtime code that uses these tables

- `api/audio-track.php` writes to `audio_play_events` and, when a member session exists, upserts `user_song_progress`.
- `api/video-track.php` writes to `video_watch_events` and, when a member session exists, upserts `user_video_progress` and `user_episode_progress`.
- `api/playlist.php` uses the existing `playlists` and `playlist_songs` tables.
- `includes/membership.php` reads `users`, `user_subscriptions`, and `subscription_plans` to resolve access level.
- `episode.php` and `watch.php` use the video access levels seeded by `database/migrations/001_membership_video_tracking.sql`.

## Environment variables for database persistence

- `SF_DB_HOST`
- `SF_DB_NAME`
- `SF_DB_USER`
- `SF_DB_PASS`
- `SF_DB_CHARSET` optional, defaults to `utf8mb4`
- `SF_HASH_SALT` optional, used for IP/user-agent event hashing


- `database/migrations/002_video_playlist_runtime_seed.sql` — aligns episode/video seed records and expected video file paths with `episode.php` and `watch.php`.


## Admin Media Catalog Manager v1

Admin v1 uses the existing schema and does not add a required migration.

New admin files:

- `admin/music.php` — dashboard using counts from `albums`, `songs`, `episodes`, `videos`, `media_assets`, and `content_access_grants`.
- `admin/music-albums.php` — manages `albums` and references `media_assets` for cover art.
- `admin/music-songs.php` — manages `songs` and `song_files`.
- `admin/episodes.php` — manages `episodes`.
- `admin/videos.php` — manages `videos` and `video_files`.
- `admin/media-access.php` — manages `subscription_plans` and `content_access_grants`.
- `admin/uploads.php` — manages `media_assets` path registry.
- `includes/admin_catalog.php` — shared helper for CRUD, fallback mode, and audit logging.

SQL required before full admin saving:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`

Without DB credentials, admin pages run in static preview mode and disable save/delete actions.

## Member Auth + Access Gates v1 SQL usage

No new migration was added. The auth/access build uses the existing base SQL and migrations 001/002. Key runtime tables: `users`, `user_auth_tokens`, `login_attempts`, `subscription_plans`, `user_subscriptions`, `content_access_grants`, `playlists`, `playlist_songs`, `audio_play_events`, `user_song_progress`, `video_watch_events`, `user_video_progress`, and `user_episode_progress`.

## Media Upload + Storage v1 SQL usage

New migration:

- `database/migrations/003_media_upload_storage_metadata.sql`

Purpose:

- Extends `media_assets` with upload metadata while preserving compatibility with the original table.
- Adds metadata for original filename, MIME type, file size, checksum, storage disk, uploaded admin/user id, and update timestamp.
- Adds indexes for type/usage and uploaded-by lookups.

Runtime/admin files using this migration:

- `admin/uploads.php` uploads local files and registers media assets.
- `includes/admin_catalog.php` contains upload validation, file movement, asset preview, dynamic insert/update helpers, and picker helpers.
- `admin/music-albums.php` uses image assets for album covers.
- `admin/music-songs.php` uses image assets for covers and audio assets for song file variants.
- `admin/videos.php` uses image assets for posters and video assets for video file variants.

Current recommended SQL import order:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`
4. `database/migrations/003_media_upload_storage_metadata.sql`

## Analytics Dashboard v1 SQL usage

No new migration was added for Analytics Dashboard v1.

New files:

- `includes/admin_analytics.php`
- `admin/analytics.php`
- `admin/audio-analytics.php`
- `admin/video-analytics.php`
- `admin/member-activity.php`

Tables read by analytics pages:

- `audio_play_events`
- `user_song_progress`
- `video_watch_events`
- `user_video_progress`
- `user_episode_progress`
- `users`
- `user_subscriptions`
- `subscription_plans`
- `playlists`
- `songs`
- `albums`
- `videos`
- `episodes`
- `orders`
- `order_items`

Required SQL import order remains:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`
4. `database/migrations/003_media_upload_storage_metadata.sql`


## `database/migrations/004_billing_entitlements.sql`

Adds the billing and subscription checkout foundation used by `subscribe.php`, `billing-checkout.php`, `billing-success.php`, `account-billing.php`, `admin/billing.php`, `api/billing-webhook.php`, and `includes/billing.php`.

Creates: `billing_customers`, `subscription_checkouts`, `invoices`, `payment_transactions`, and `billing_webhook_events`. Updates: `subscription_plans` and `user_subscriptions`.


## Migration 005 — Merch Cart + Order Runtime

`database/migrations/005_merch_order_runtime.sql` adds optional order runtime columns plus `order_status_history` and `product_inventory_movements`. It supports `includes/store.php`, `cart.php`, `checkout.php`, `order-confirmation.php`, `admin/products.php`, `admin/orders.php`, and `api/cart.php`.


## Migration 006 — Email + Notifications

File: `database/migrations/006_email_notifications.sql`

Adds `email_templates`, `notification_logs`, `notification_preferences`, and `notification_webhook_events`. Used by `includes/notifications.php`, `admin/notifications.php`, `admin/email-templates.php`, `api/notification-webhook.php`, auth, billing, merch order, and fulfillment flows.

## Added in Next Stages Combined Build

### `database/migrations/007_site_settings_installer.sql`
Adds:
- `site_settings`
- `system_installation_checks`

Used by:
- `includes/settings.php`
- `install.php`
- `admin/settings.php`
- `admin/system-health.php`

### `database/migrations/008_payment_gateway_adapter.sql`
Adds:
- `payment_gateway_settings`
- `payment_gateway_webhook_events`

Used by:
- `includes/payment_gateway.php`
- `admin/payment-gateways.php`
- `api/payment-webhook.php`
- `billing-checkout.php`
- `checkout.php`

### `database/migrations/009_episode_video_admin_v2.sql`
Adds:
- `seasons`
- `video_chapters`

Updates:
- `episodes`
- `videos`

Used by:
- `admin/seasons.php`
- `admin/episodes.php`
- `admin/release-schedule.php`
- `admin/videos.php`

### `database/migrations/010_production_readiness_qa_harness.sql`

Adds:
- `qa_runs`
- `qa_check_results`

Used by:
- `includes/qa.php`
- `admin/qa.php`
- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`

This migration is optional for viewing QA pages, but required to persist QA run history and individual check results.

Updated required SQL import order:

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
