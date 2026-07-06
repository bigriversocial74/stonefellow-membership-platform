# Review Notes

This pass consolidates the admin navigation registry and QA registry into source-of-truth helper functions.

No database migration is added.

Suggested post-merge checks:

- Open `admin/index.php`.
- Confirm sidebar links render.
- Open `admin/launch-checklist.php`.
- Open `admin/routes-checker.php`.
- Open `admin/migration-checker.php`.
- Open `deploy/preflight.php`.
