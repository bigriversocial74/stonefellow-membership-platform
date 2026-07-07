# Phase 43 — Scene Actions v1

## Purpose

Phase 43 adds scene-level operations to the Stonefellow Storyboarding module.

The storyboarding builder can now work scene-by-scene after the initial 9-scene generation pass. Each scene can be edited, rewritten, regenerated visually, or replaced with an uploaded image.

## Added runtime surfaces

- `includes/storyboard_scene_actions.php` — scene action helper for persistence, rewrite, image regeneration, manual image upload, character consistency context, and retry-ready job handling.
- `api/storyboard-scene-action.php` — admin-only POST endpoint for scene actions.
- `docs/PHASE_43_SCENE_ACTIONS.md` — this phase note.

## Updated runtime surfaces

- `admin/storyboard-builder.php` now includes scene-level forms for:
  - Save Scene
  - Rewrite Scene
  - Regenerate Image
  - Upload Image
- `includes/storyboards.php` now exposes database scene IDs and editable scene fields to the builder.
- `admin/index.php` marks Phase 43 as built.
- `includes/package_readiness.php` includes the scene action helper, endpoint, and docs in package checks.

## Scene actions

### Edit Scene

Saves the following fields to `storyboard_scenes`:

- `scene_title`
- `scene_summary`
- `scene_prompt`
- `image_prompt`
- `dialog_text`
- `action_notes`
- `location_label`
- `time_of_day`
- `scene_status`

### Rewrite Scene

Rewrites one scene at a time using the admin-selected default text provider.

The rewrite payload includes:

- storyboard title
- visual style
- aspect ratio
- current scene number
- current scene text
- rewrite instruction
- surrounding storyboard scene summaries
- character consistency notes

The response is expected as JSON for a single scene and updates the scene record when valid.

### Regenerate Image

Regenerates one scene image using the admin-selected image provider.

The image payload includes:

- scene image prompt
- scene prompt fallback
- character consistency notes
- storyboard visual style
- instruction to avoid captions, subtitles, UI, watermarks, or text in the generated frame

Generated image files are saved into:

```txt
assets/images/uploads/storyboards/YYYY/MM/
```

A `media_assets` record is created and linked back to `storyboard_scenes.generated_image_asset_id`.

### Upload Image

Uploads a replacement scene image through the existing admin media upload helper.

The uploaded image is linked to `storyboard_scenes.uploaded_image_asset_id` and becomes the preferred visible scene image.

## Job tracking

Phase 43 uses `storyboard_jobs` for action-level tracking:

- `rewrite_scene`
- `regenerate_scene_image`
- `upload_scene_image`

Each job stores input/output JSON, status, error message, attempts, timestamps, and related scene/storyboard IDs.

## Character consistency

The scene action helper builds a character-consistency prompt block from the characters assigned to the scene.

The prompt can include:

- character name
- role
- appearance notes
- wardrobe notes
- consistency prompt
- reference image path

This is used by rewrite and image regeneration actions.

## SQL

No new SQL migration was added in Phase 43.

Phase 43 requires the Phase 41 migration:

```txt
database/migrations/021_storyboarding_ai_settings.sql
```

## Runtime requirements

- Migration 021 installed.
- Active/configured text provider for scene rewrite.
- Active/configured image provider for image regeneration.
- PHP cURL for provider calls.
- Writable `assets/images/uploads/storyboards/` path.
- Existing `media_assets` table.

## Recommended next phase

Phase 44 should add a cleaner modal/UX layer and character management actions:

- Add Character persistence
- Upload Character Reference action
- Assign/remove scene characters
- Scene action modals instead of inline forms
- Job status badges and retry buttons in the builder UI
- Bulk regenerate all scene images
