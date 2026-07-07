# Phase 40 — Storyboarding Module Shell v1

## Purpose

Phase 40 adds the first production UI shell for a Stonefellow storyboarding module. The module is designed for a creator workflow where a user enters a basic script prompt and later AI phases expand it into a structured 9-scene screenplay with images, dialog, character references, and per-scene actions.

## Added runtime surfaces

- `includes/storyboards.php` — static shell helper with starter storyboard projects, storyboard settings, character reference profiles, and a 9-scene sample screenplay grid.
- `admin/storyboards.php` — storyboard project list page with create/open workflow and the planned production build path.
- `admin/storyboard-builder.php` — creator-facing storyboard builder shell with script prompt, storyboard settings, character references, 9 scene cards, and scene action controls.

## Updated surfaces

- `includes/admin_catalog.php` adds Storyboarding to the admin sidebar.
- `admin/index.php` adds Storyboarding to the dashboard and marks Phase 40 as built.
- `includes/package_readiness.php` registers the storyboarding helper, admin pages, and Phase 40 docs in the package manifest and readiness checks.
- `assets/css/admin-polish.css` adds storyboard-specific card, thumbnail, character, and 3x3 scene grid styles.

## What the shell includes

The builder page includes:

- script prompt field
- Generate 9-Scene Storyboard button
- creator workflow settings panel
- read-only AI Provider: Admin Managed badge
- characters/reference section
- Add Character button
- Upload Reference button
- Consistency Settings button
- 9-scene storyboard grid
- per-scene generated image frame placeholder
- scene title
- scene prompt
- dialog snippet
- character chips
- Edit button
- Rewrite Scene button
- Regenerate Image button
- Upload Image button

## API key boundary

The user workspace does not include API key fields, masked keys, provider setup, or key-status management.

API credentials belong in a later admin-only AI settings phase.

## SQL

No SQL migration was added in Phase 40.

The module is intentionally static shell data only. Persistence should be added in the next phase.

## Recommended next phase

Phase 41 should add:

- storyboard persistence tables
- scene persistence tables
- character/reference-image persistence tables
- storyboard job queue tables
- admin AI settings table
- provider/default model settings
- usage limits and cost tracking structure

## Planned production flow

1. User creates a storyboard project.
2. User enters a basic script prompt.
3. System expands the prompt into 9 structured scenes.
4. Each scene stores title, prompt, dialog, notes, characters, image prompt, and image status.
5. Characters store likeness/reference images and consistency notes.
6. Scene image generation uses scene prompt plus character references.
7. User can edit, rewrite, regenerate image, or upload an image for each scene.
8. Storyboard can later export to screenplay, visual board, or production package.
