# Phase 37 — Post-Merge Production Verification + Script Package Readiness

## Purpose

Phase 37 adds a production package-readiness layer after PR #22. The goal is to verify that the script is ready to be packaged, uploaded, installed, and smoke-tested from `main` without relying on memory or informal checklists.

## Added runtime surfaces

- `includes/package_readiness.php` — shared helper for package manifest, required file checks, package scoring, grouped summaries, and deploy handoff status.
- `admin/package-readiness.php` — admin package readiness page with score cards, grouped package summaries, deployable file manifest, SHA-256 hashes, and handoff actions.
- `deploy/preflight.php` — expanded plain-text preflight with QA score, package score, required file counts, section summaries, blocking failures, review items, and launch gate status.

## Updated surfaces

- `admin/index.php` now includes Package Readiness in the Launch Gate section and records Phase 37 as built.
- `docs/DEPLOYMENT_RUNBOOK.md` now includes the package readiness gate before backup/release/deployment.

## SQL

No SQL migration was added.

Current install target remains:

```txt
database/stonefellow_streaming_platform.sql
+ database/migrations/001_* through 020_*
```

Existing installs should apply missing migrations sequentially through:

```txt
database/migrations/020_monitoring_incident_alerts.sql
```

## Deployment handoff sequence

1. Upload the current `main` package.
2. Run `install.php` on fresh installs, or apply missing SQL migrations on existing installs.
3. Open `admin/package-readiness.php`.
4. Open `admin/migration-checker.php`.
5. Open `admin/routes-checker.php`.
6. Open `admin/qa.php`.
7. Run `deploy/preflight.php`.
8. Create/confirm backup and release records.
9. Smoke-test auth, member pages, player/watch routes, merch checkout, monitoring, incidents, backups, releases, and APIs.

## Launch gate

Treat the package as launch-ready only when:

- package readiness has no missing required files
- migration target is confirmed through `020`
- route registry has no missing required routes
- production QA has no blocking failures
- preflight returns no blocking failures
- backup and release records exist for the deploy
