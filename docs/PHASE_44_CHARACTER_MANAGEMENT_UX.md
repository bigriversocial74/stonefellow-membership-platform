# Phase 44 — Storyboard Character Management + UX Modals v1

## Purpose

Phase 44 upgrades the Storyboarding builder from a basic scene-action grid into a more complete production workspace for character management, reference images, scene assignment, job status, retry actions, and bulk image regeneration.

## Added runtime surfaces

- `includes/storyboard_character_actions.php` — character/action helper for add, update, reference upload, scene assignment, scene removal, bulk image regeneration, and recent job lookup.
- `api/storyboard-character-action.php` — admin-only POST endpoint for character and bulk storyboard actions.
- `docs/PHASE_44_CHARACTER_MANAGEMENT_UX.md` — this phase note.

## Updated runtime surfaces

- `admin/storyboard-builder.php` now uses `includes/storyboard_character_actions.php` and exposes a cleaner expandable panel workflow.
- `admin/index.php` marks Phase 44 as built.
- `includes/package_readiness.php` registers the character helper, endpoint, and docs in package readiness checks.

## Character management

The builder now supports:

- Add Character
- Update Character
- Upload Character Reference
- Likeness strength setting
- Appearance notes
- Wardrobe notes
- Consistency prompt notes
- Primary reference image linking through `media_assets`

## Scene character assignment

Each scene action panel can now:

- Assign a character to the scene
- Remove a character from the scene

This updates `storyboard_scene_characters` and improves the character-consistency payload used by rewrite and image regeneration.

## UX modal pattern

Phase 44 uses native expandable `details` panels as a low-risk modal-style workflow.

This keeps the interface production-safe without adding heavy JavaScript dependencies yet.

The builder now groups actions under:

- Add Character
- Manage Character
- Scene Actions
- Recent Jobs

## Job status and retry controls

The builder now includes a recent storyboard jobs table showing:

- job type
- provider key
- scene number/title
- status badge
- updated timestamp
- retry action

Retry support is delegated to the existing scene action retry helper.

## Bulk image regeneration

The Storyboard Settings panel now includes a bulk action for regenerating all scene images.

The helper runs scene image generation across all scenes attached to the storyboard.

## SQL

No new SQL migration was added.

Phase 44 uses the Phase 41 migration:

```txt
database/migrations/021_storyboarding_ai_settings.sql
```

## Runtime requirements

- Migration 021 installed.
- Writable image upload path.
- Existing `media_assets` table.
- Active/configured image provider for bulk regeneration.
- Active/configured text provider for rewrite actions.

## Recommended next phase

Phase 45 should add production polish and queue safety:

- Background queue worker for image generation instead of synchronous bulk generation
- Job progress polling
- Per-job cancel controls
- Bulk image generation queue batching
- Storyboard export to screenplay/shot list
- Character reference gallery management
- Better front-end modal JavaScript layer
