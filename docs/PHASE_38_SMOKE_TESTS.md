# Phase 38 — Production Smoke Test Runner / QA Scenario Matrix v1

## Purpose

Phase 38 adds a production smoke-test matrix that sits after package readiness and before final deploy handoff. It gives admins a practical launch checklist that covers real platform behavior, not just file/package presence.

## Added runtime surfaces

- `includes/smoke_tests.php` — shared smoke-test scenario matrix, static evaluator, grouped summaries, scoring, counts, and table renderer.
- `admin/smoke-tests.php` — admin smoke-test runner page with scenario scores, grouped launch coverage, route/API status, and manual production checks.

## Updated surfaces

- `deploy/preflight.php` now includes smoke-test scoring and grouped scenario output in the final deploy gate.
- `admin/index.php` now links to Smoke Tests from the Launch Gate section and marks Phase 38 as built.
- `includes/package_readiness.php` now includes smoke-test files and docs in the deployable package manifest.
- `docs/DEPLOYMENT_RUNBOOK.md` now includes the smoke-test page and final smoke-test launch gate.

## Smoke-test groups

The matrix covers:

- install and launch gate
- auth and account routes
- member runtime
- media playback
- commerce and billing
- admin operations
- API JSON contracts
- manual production checks

## What is automatic

The smoke-test helper automatically verifies:

- required route files exist
- API endpoints exist
- API files expose a JSON response helper/header contract
- grouped scenario scores
- launch-blocking missing files
- warnings/manual review counts

## What stays manual

These checks must be verified in a live browser/provider environment:

- payment gateway keys and webhook test events
- transactional email delivery
- production backup export/preservation
- release record details
- subscriber-only media access and visitor denial

## SQL

No SQL migration was added.

Current install target remains base schema plus migrations `001` through `020`.

Existing installs should apply missing migrations sequentially through:

```txt
database/migrations/020_monitoring_incident_alerts.sql
```

## Deployment handoff sequence

1. Open `admin/package-readiness.php`.
2. Open `admin/smoke-tests.php`.
3. Resolve missing route/API failures.
4. Complete or intentionally waive manual production checks.
5. Run `deploy/preflight.php`.
6. Confirm no blocking failures.
7. Create backup and release records.
8. Smoke-test auth, member pages, player/watch routes, merch/checkout, admin ops, monitoring, incidents, backups, releases, and APIs in browser.
