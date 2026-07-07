# Phase 39 — Production Release Candidate / Final Deploy Handoff v1

## Purpose

Phase 39 adds the final release-candidate handoff layer for Stonefellow. It combines production QA, package readiness, smoke-test coverage, migration target, latest backup status, release record status, release checklist tasks, and final launch status into one admin surface.

## Added runtime surfaces

- `includes/release_candidate.php` — shared helper for release-candidate checks, final deploy summary, grouped gate scoring, backup/release status, release task summary, package metadata, and final status text.
- `admin/release-candidate.php` — final release-candidate admin page with combined launch scores, migration target, QA/package/smoke/release gates, and final deploy sequence.

## Updated surfaces

- `deploy/preflight.php` includes release-candidate score, release-candidate grouped gates, repository target, branch target, migration target, and final launch gate status.
- `admin/index.php` links to Release Candidate from the Launch Gate section and marks Phase 39 as built.
- `includes/package_readiness.php` includes release-candidate helper, admin page, and Phase 39 docs in the deployable file manifest.
- `docs/DEPLOYMENT_RUNBOOK.md` references the release-candidate page and final handoff gate.

## Release candidate gates

The release-candidate helper checks:

- repository and target branch
- package archive target
- migration target through `020`
- production QA status
- package readiness status
- smoke-test status
- preflight route presence
- latest backup status
- latest release record status
- latest release checklist task status
- deployment runbook presence
- Phase 39 documentation presence

## SQL

No SQL migration was added.

Current install target remains the base schema plus migrations `001` through `020`.

Existing installs should apply only missing migrations sequentially through `database/migrations/020_monitoring_incident_alerts.sql`.

## Final handoff sequence

1. Confirm the release-candidate PR is merged into `main`.
2. Confirm the package target is the `main` branch archive.
3. Create or verify a completed backup record.
4. Create or update a deployment release record.
5. Confirm migration target `020`.
6. Open `admin/release-candidate.php`.
7. Open `admin/package-readiness.php`.
8. Open `admin/smoke-tests.php`.
9. Run `deploy/preflight.php`.
10. Complete or intentionally waive manual release checks.
11. Re-test auth, member runtime, media playback, merch/checkout, admin ops, monitoring, incidents, backups, releases, and APIs.

## Launch decision

Launch only when:

- release-candidate page has no blocking failures
- package readiness has no missing required files
- smoke-test matrix has no missing route/API failures
- preflight has no blocking failures
- backup record is completed or verified
- release record is ready, deploying, or deployed
- release checklist has no failed or pending tasks unless intentionally waived
- payment, email, protected media, and admin access are verified manually
