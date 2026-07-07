# Final Production QA / Route Registry v2

## Scope

This pass aligns the final launch QA surface with the current Stonefellow platform state through migration `020`.

## Updated coverage

- Public and member route registry now includes feed, notifications, messages, comments, support, and logout routes.
- Admin route registry now includes monitoring, incidents, backups, releases, roles, security dashboard, lifecycle, support, tier manager, revenue dashboard, and engagement analytics.
- API route registry now includes comments, notifications, member messages, ops scheduler, backup readiness, release manager, monitoring, and incidents endpoints.
- Migration plan now tracks base schema plus migrations `001` through `020`.
- Required table/column checks now include the newer feed, revenue, lifecycle, scheduler, messaging, role/security, backup/release, monitoring, incident, and alert tables.
- Launch checklist now references the current SQL order through migration `020` and includes backup/release/monitoring gates.

## SQL

No new SQL migration is required for this phase.

Existing installs must still apply migrations sequentially through:

```txt
database/migrations/020_monitoring_incident_alerts.sql
```

Migration `020` must run after migration `019`.

## Validation checklist

- Run `admin/migration-checker.php` after SQL import.
- Run `admin/routes-checker.php` after file upload.
- Run `admin/qa.php` after routes and SQL are available.
- Run `deploy/preflight.php` before launch.
- Verify `admin/monitoring.php` and `admin/incidents.php` after migration `020`.
- Verify `admin/backups.php` and `admin/releases.php` after migration `019`.
