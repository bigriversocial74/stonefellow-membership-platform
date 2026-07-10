# Stonefellow Launch Content & Catalog Operations v1

## Score

- Initial source readiness: **6.4/10**
- Final source/control score: **10/10**

The operational catalog score remains data-driven. It reaches 100% only after real Stonefellow records, relationships, media, SEO, commerce, and release details pass every readiness check.

## Capabilities

1. Unified readiness across series, seasons, episodes, videos, albums, songs, cast/characters, merchandise, and membership plans.
2. Weighted checks for titles, slugs, descriptions, images, SEO, relationships, ordering, access, media processing, pricing, inventory, billing intervals, and entitlements.
3. Immutable readiness snapshots tied to an optional exact release commit SHA.
4. Explicit SEO title, description, canonical path, social image, and noindex controls.
5. IANA-timezone scheduling normalized to UTC.
6. Draft-first CSV/JSON imports and complete CSV exports for every launch catalog type.
7. High-confidence sample-content scanning without destructive automatic deletion.
8. Publication and cleanup batches with before/after evidence and rollback.
9. Signed, POST-only, idempotent due-publishing runner.
10. Permanent smoke, static audit, and whole-platform certification gates.

## SQL

Import:

`database/migrations/024_launch_content_catalog_operations.sql`

The migration creates new tables only and does not use `ADD COLUMN IF NOT EXISTS`.

## Admin pages

- `/admin/catalog-operations.php`
- `/admin/catalog-transfer.php`
- `/admin/release-schedule.php`
- `/admin/media-pipeline.php`
- `/admin/payment-gateways.php`

## Scheduler

POST to `/api/catalog-operations-tick.php` with:

- `X-SF-Catalog-Secret`: the value of `SF_CATALOG_RUNNER_SECRET`
- `X-SF-Idempotency-Key`: a unique 8–64 character event key

The runner promotes due scheduled content through the existing publishing service and stores a new catalog readiness snapshot.

## Launch sequence

1. Import migration 024.
2. Configure `SF_CATALOG_TIMEZONE`, `SF_CATALOG_RUNNER_SECRET`, and the exact release commit.
3. Populate and process the real catalog.
4. Complete explicit SEO metadata.
5. Save readiness snapshots until the operational score reaches 100%.
6. Scan and review sample-content flags.
7. Export a catalog backup.
8. Publish ready records through a reversible publication batch.
9. Complete browser, payment, media, and release-candidate testing.
