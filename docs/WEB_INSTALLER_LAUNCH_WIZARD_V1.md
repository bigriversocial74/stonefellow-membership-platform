# Web Installer + Launch Wizard v1

This phase upgrades `install.php` from a health-check page into a one-time installer/launcher.

## Goal

Upload the Stonefellow script, visit the site URL, enter database credentials, run all SQL files, create the first admin user, save site settings, lock the installer, and launch the admin dashboard.

## Installer flow

1. **Server Check**
   - PHP 8.1+
   - PDO / PDO MySQL
   - JSON and fileinfo extensions
   - writable `config/`, `storage/`, and upload directories
   - SQL files present

2. **Database Setup**
   - host
   - port
   - database name
   - database user
   - database password
   - connection test

3. **SQL Install**
   - creates `schema_migrations`
   - runs the base SQL file
   - runs migrations `001` through `011`
   - records applied file checksums
   - skips already-applied files with matching checksums

4. **Site + Admin Setup**
   - site name
   - tagline
   - base URL
   - support email
   - first admin name/email/password
   - creates or updates the admin account
   - saves public site settings when the `site_settings` table exists

5. **Finish + Lock**
   - writes `config/local.php`
   - writes `storage/install.lock`
   - signs in the first admin session
   - redirects to `admin/index.php`

## Runtime files written by installer

These files are generated on the server and are intentionally ignored by Git:

- `config/local.php`
- `storage/install.lock`

## Automatic launcher behavior

`includes/config.php` now redirects PHP page requests to `install.php` when `storage/install.lock` is missing. This creates the upload-and-visit installer experience.

After the lock file exists, the normal app loads.

## SQL order

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

## Rerunning the installer

Only rerun after a database backup.

To rerun:

1. Remove `storage/install.lock`.
2. Visit `install.php`.
3. Keep or replace the existing database credentials.

Existing SQL files with matching checksums are skipped through `schema_migrations`.

## Security notes

- `config/local.php` contains database credentials and must not be committed.
- `config/.htaccess` and `storage/.htaccess` deny direct web access on Apache hosts.
- On non-Apache servers, protect `config/` and `storage/` at the web-server level.
- After install, folder permissions can be tightened.
