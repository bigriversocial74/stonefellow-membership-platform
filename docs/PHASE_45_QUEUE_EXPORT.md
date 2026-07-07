# Phase 45 — Storyboard Queue + Export v1

## Purpose

Phase 45 adds queue-style image generation controls, job progress/status utilities, export downloads, and character reference gallery review to the Storyboarding workspace.

This phase prepares the module for production-safe background processing without requiring a permanent worker yet.

## Added runtime surfaces

- `includes/storyboard_queue_export.php` — queue helper, process-next worker helper, cancel helper, job summary, reference gallery, and export builders.
- `api/storyboard-queue.php` — admin-only POST endpoint for queue actions.
- `api/storyboard-export.php` — admin-only download endpoint for screenplay, shot list CSV, and JSON exports.
- `docs/PHASE_45_QUEUE_EXPORT.md` — this phase note.

## Updated runtime surfaces

- `admin/storyboard-builder.php` now includes queue controls, export links, job summary cards, process-next control, cancel controls, and reference gallery review.
- `admin/index.php` marks Phase 45 as built.
- `includes/package_readiness.php` registers queue/export helper, endpoints, and docs.

## Queue actions

The queue endpoint supports:

- `enqueue_scene_image`
- `enqueue_bulk_images`
- `process_next`
- `cancel_job`
- `status`

Queued image jobs use the existing `storyboard_jobs` table from migration 021.

## Process-next worker behavior

`sf_sbq_process_next_image_job()` finds the next queued image job and processes one job at a time.

This provides a simple manual/admin worker action now and prepares the same logic for a cron or background runner later.

## Export formats

The export endpoint supports:

- `format=screenplay` — plain text screenplay export
- `format=shotlist` or `format=csv` — CSV production shot list
- `format=json` — complete storyboard package with storyboard, characters, and scenes

## Builder UX

The builder now includes:

- Queue All Scene Images
- Process Next Image Job
- Export Screenplay
- Export Shot List CSV
- Export JSON
- Reference Gallery table
- Job summary counts
- Cancel job controls
- Scene-level Queue Scene Image action

## SQL

No new SQL migration was added.

Phase 45 uses migration `021_storyboarding_ai_settings.sql`.

## Runtime requirements

- Migration 021 installed.
- Active/configured image provider for queue processing.
- PHP cURL for provider calls.
- Writable storyboard image upload path.
- Existing `media_assets` table.

## Recommended next phase

Phase 46 should add:

- real background/cron worker entry point
- job progress polling JavaScript
- dedicated storyboard export page/preview
- PDF export option
- screenplay formatting polish
- storyboard package ZIP export
- job log detail drawer
