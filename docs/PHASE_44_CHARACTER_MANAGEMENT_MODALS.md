# Phase 44 — Storyboard Character Management + UX Modals v1

## Purpose

Phase 44 upgrades the Storyboarding workspace from basic inline scene actions into a more complete storyboard production workflow.

It adds character management, character reference uploads, scene-character assignment, modal-style controls, job status visibility, retry actions, and bulk scene image regeneration.

## Added runtime surfaces

- `api/storyboard-characters.php` — admin-only POST endpoint for character and storyboard-level actions.

## Updated runtime surfaces

- `includes/storyboard_scene_actions.php` now includes character management helpers and bulk image helpers.
- `includes/storyboards.php` now exposes detailed character fields and raw scene status values for edit forms.
- `admin/storyboard-builder.php` now uses modal-style controls for character and scene workflows.
- `admin/index.php` marks Phase 44 as built.
- `includes/package_readiness.php` registers the Phase 44 endpoint, docs, and readiness checks.

## Character management

The storyboard builder now supports:

- Add Character
- Edit Character
- Upload Character Reference
- Status management: active, hidden, archived
- Likeness strength: loose, medium, strong
- Appearance notes
- Personality notes
- Wardrobe notes
- Consistency prompt
- Character ordering

Character reference uploads use the existing admin media upload helper and update:

- `storyboard_characters.reference_asset_id`
- `storyboard_character_references`
- `media_assets`

## Scene character assignment

Each scene now has a character assignment modal.

The modal lets the admin select which storyboard characters appear in that scene. The selected values update `storyboard_scene_characters`.

This directly improves the scene rewrite and image generation payloads because the character-consistency helper uses assigned scene characters first.

## UX modal layer

The builder now uses modal-style controls for:

- Add Character
- Edit Character
- Upload Character Reference
- Edit Scene
- Rewrite Scene
- Assign Scene Characters
- Scene Image actions
- Bulk Regenerate Images

The intent is to keep scene cards readable while still exposing production-level controls.

## Job status and retry

The builder now shows a recent scene jobs panel.

The jobs panel displays:

- job type
- job status
- scene ID
- provider key
- updated timestamp
- error message snippet
- retry button for failed supported jobs

Retry currently supports:

- `rewrite_scene`
- `generate_scene_image`
- `regenerate_scene_image`

## Bulk image regeneration

The builder includes a storyboard-level bulk image regeneration action.

This loops through all scenes in the storyboard and calls the existing scene image regeneration helper.

Runtime requirements:

- active image provider
- configured image provider key
- PHP cURL
- writable `assets/images/uploads/storyboards/` path

## SQL

No new SQL migration was added in Phase 44.

Phase 44 uses the Phase 41 migration:

```txt
database/migrations/021_storyboarding_ai_settings.sql
```

## Recommended next phase

Phase 45 should add a dedicated storyboard production review/export layer:

- screenplay export view
- storyboard PDF export
- shot list export
- image prompt export
- character bible export
- production package download
- approval/review status by scene
