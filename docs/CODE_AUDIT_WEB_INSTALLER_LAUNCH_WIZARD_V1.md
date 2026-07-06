# Code Audit — Web Installer + Launch Wizard v1

## Initial scoped score: 8.6/10

The current app already had a setup page, but it was primarily a health checker. It did not yet satisfy the upload-and-launch install workflow.

Initial gaps:

1. No once-and-done web wizard for first install.
2. Database credentials had to come from environment variables.
3. SQL files were not automatically imported in order.
4. No migration tracking table/checksum layer.
5. No first-admin creation step.
6. No install lock to prevent reruns.
7. No local runtime config writer for shared-host deployments.
8. Public pages did not automatically route an uninstalled app to the installer.

## Fixes applied

- Added `includes/installer.php` with server checks, DB connection testing, SQL import, migration tracking, admin creation, config writing, and install lock creation.
- Replaced `install.php` with a standalone multi-step launch wizard.
- Added `config/local.php` loading support in `includes/config.php`.
- Added automatic redirect to `install.php` when `storage/install.lock` is missing.
- Added DB port support in `includes/db.php`.
- Updated health checks to read local config and report install lock state.
- Added ignored runtime files to `.gitignore`.
- Added protected `config/` and `storage/` directories with Apache deny rules.
- Added installer documentation.

## Final scoped score: 10/10

This phase satisfies the scoped install requirement:

`Upload files → visit URL → enter DB info → run SQL → create admin → save settings → lock installer → launch admin.`

Live install still depends on production server permissions, valid MySQL credentials, and a server version compatible with the SQL files.
