# Phase 41 — Storyboarding SQL + Admin AI Settings v1

## Purpose

Phase 41 adds the persistence foundation for the storyboarding module and moves AI provider configuration into an admin-only area.

The creator-facing storyboard workspace continues to show only this boundary:

```txt
AI Provider: Admin Managed
```

No provider credential fields are shown in the storyboard builder.

## Added SQL

New migration:

```txt
database/migrations/021_storyboarding_ai_settings.sql
```

The migration adds:

- `storyboards`
- `storyboard_scenes`
- `storyboard_characters`
- `storyboard_character_references`
- `storyboard_scene_characters`
- `storyboard_jobs`
- `ai_provider_settings`
- `ai_usage_events`

It also seeds provider records for:

- `chatgpt`
- `claude`

## Added runtime surfaces

- `includes/ai_settings.php` — admin AI provider helper, provider list, save handling, key masking, encryption wrapper, usage summary, and provider options.
- `admin/ai-settings.php` — admin-only AI provider settings page.

## Updated runtime surfaces

- `includes/storyboards.php` is now database-aware and falls back to static shell data when migration 021 is not installed.
- `admin/storyboards.php` now supports database-backed project creation when migration 021 is installed.
- `admin/storyboard-builder.php` now reads database-backed storyboard, character, and scene records when they exist.
- `admin/index.php` now links to AI Settings and marks Phase 41 as built.
- `includes/package_readiness.php` now includes AI settings and migration 021 in package checks.
- `docs/SQL_FILE_MAP.md` documents migration 021.

## Admin AI settings

The admin page supports:

- provider label
- provider type
- default text model
- image model
- provider status
- default text provider flag
- default image provider flag
- monthly budget limit
- monthly token limit
- monthly image limit
- timeout seconds
- retry count
- temperature
- new provider secret entry
- masked provider secret status
- current-month usage summary

## Security boundary

Provider secret entry is admin-only.

The helper uses OpenSSL AES-256-GCM when available and refuses to save provider secrets when that encryption support is missing.

Recommended production environment variables:

```txt
SF_AI_SETTINGS_SECRET=<strong random secret>
SF_MEDIA_SIGNING_KEY=<existing strong signing key fallback>
SF_HASH_SALT=<existing hash salt fallback>
```

## Install notes

Fresh installs currently run through migration `020` through the installer plan. Until the installer plan is advanced, apply migration `021` after the installer completes.

Existing installs should apply only this missing migration after backup:

```txt
database/migrations/021_storyboarding_ai_settings.sql
```

## Recommended next phase

Phase 42 should add Script-to-9-Scene Generation API v1:

- admin provider lookup
- prompt normalization
- model request payload builder
- structured JSON scene parser
- storyboards/scenes persistence
- storyboard job status updates
- error logging
- usage event recording
- safe no-key / disabled-provider handling
