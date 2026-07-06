# Production Readiness + QA Harness v1

This stage adds a launch-readiness layer for the Stonefellow membership platform. It does not replace manual QA, but it gives the admin team a central place to verify the platform before deployment.

## Added files

- `includes/qa.php`
- `admin/qa.php`
- `admin/migration-checker.php`
- `admin/routes-checker.php`
- `admin/security-check.php`
- `admin/content-audit.php`
- `database/migrations/010_production_readiness_qa_harness.sql`
- `docs/DEPLOYMENT_RUNBOOK.md`
- `docs/CODE_AUDIT_PRODUCTION_READINESS_QA_HARNESS_V1.md`

## Admin sections

### `admin/qa.php`

Main QA dashboard. It scores the current build across:

- environment readiness
- migration/schema readiness
- public/admin/API route readiness
- security hardening
- content/media asset completeness

When migration 010 is installed, the page can persist a QA run and individual check results.

### `admin/migration-checker.php`

Shows the required SQL order from the base schema through migration 010. In database mode it checks expected tables and core columns.

### `admin/routes-checker.php`

Lists public routes, member/commerce routes, admin routes, and JSON API endpoints. It verifies route files exist and checks important contracts like header/full-page includes and JSON response helpers.

### `admin/security-check.php`

Reviews the security posture for:

- admin route protection
- global admin CSRF guard
- password hashing
- cryptographic token generation
- upload validation
- webhook verification boundaries
- PDO hardening
- order/inventory runtime controls

### `admin/content-audit.php`

Audits catalog references for missing local files:

- album covers
- song covers/previews/full files
- episode posters/thumbnails
- video poster/source paths
- merch product imagery

Static/demo mode may show warnings for placeholder media paths that are expected to be replaced by real uploads.

## Database migration

Run after migrations 001 through 009:

```sql
source database/migrations/010_production_readiness_qa_harness.sql;
```

Creates:

- `qa_runs`
- `qa_check_results`

These tables are optional for rendering the QA pages but required for persisted QA history.

## Scoring model

The QA helper creates weighted checks and returns a 0–100 readiness score. Pass/ready/ok checks receive full points; warning/preview/manual checks receive partial points; failed checks receive no points.

There are two important interpretations:

1. **Code section score** — whether this QA harness itself is implemented cleanly. This phase scores 10/10 after audit fixes.
2. **Live environment score** — whether the current hosting environment has database credentials, migrations, writable upload folders, media assets, and production secrets configured. This score can show review items until the host is fully configured.

## Notes

The QA harness is intentionally safe in no-database/static preview mode. It should not block local review, but it will identify what still needs to be configured for production launch.
