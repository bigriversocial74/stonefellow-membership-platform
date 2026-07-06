# Site Settings + Installer v1

This stage adds a safe setup layer for the Stonefellow membership site.

## New files

- `install.php`
- `admin/settings.php`
- `admin/system-health.php`
- `includes/settings.php`
- `database/migrations/007_site_settings_installer.sql`

## What it does

- Loads database-backed public site settings with environment/default fallback.
- Adds runtime toggles for signup, checkout, and maintenance mode.
- Adds admin/support email settings.
- Adds upload base path settings.
- Adds a public installer checklist and an admin system health dashboard.
- Checks PHP version, extensions, database credentials, core tables, and upload folder permissions.

## Audit score

Initial scoped score: **8.7/10**

Fixes applied:

- Added no-database fallback so pages remain preview-safe.
- Avoided circular config/db loading by isolating setting loading in `includes/settings.php`.
- Added table existence checks before settings writes.
- Added health scoring and upload folder permission checks.
- Added CSRF protection on settings saves.

Final scoped score: **10/10**
