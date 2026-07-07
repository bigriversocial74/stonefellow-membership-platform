# Phase 42 — Script-to-9-Scene Generation API v1

## Purpose

Phase 42 connects the Storyboarding builder prompt to an admin-gated generation endpoint. The endpoint uses the admin-selected text provider, asks for a structured 9-scene storyboard JSON response, parses the result, and saves scenes, characters, scene-character links, job status, and usage events into the Phase 41 tables.

## Added runtime surfaces

- `includes/storyboard_generation.php` — generation helper, provider adapter, JSON prompt builder, response parser, job handling, scene persistence, character persistence, and usage logging.
- `api/storyboard-generate.php` — admin-only POST endpoint used by the storyboard builder.
- `docs/PHASE_42_STORYBOARD_GENERATION_API.md` — this phase note.

## Updated runtime surfaces

- `admin/storyboard-builder.php` now posts the script prompt to `api/storyboard-generate.php`.
- `admin/index.php` marks Phase 42 as built.
- `includes/package_readiness.php` includes the generation helper, API endpoint, and Phase 42 docs in the required package manifest.

## Generation flow

1. Admin opens `admin/storyboard-builder.php`.
2. Admin enters or edits the basic script prompt.
3. Builder submits to `api/storyboard-generate.php`.
4. Endpoint verifies admin access and CSRF.
5. Helper loads the storyboard project.
6. Helper selects the admin-configured default text provider.
7. Helper confirms provider status and key status.
8. Helper creates a `storyboard_jobs` record with `running` status.
9. Helper requests a JSON-only 9-scene storyboard from the provider.
10. Helper parses and validates that exactly 9 scenes were returned.
11. Helper saves storyboard metadata, characters, scenes, and scene-character links.
12. Helper marks the job complete or failed.
13. Helper records an `ai_usage_events` row when available.
14. Builder redirects back to the scene grid.

## Provider support

Phase 42 includes text-generation adapters for:

- ChatGPT / OpenAI using the Responses API.
- Claude / Anthropic using the Messages API.

The generation layer does not expose provider credentials in the creator workspace. Provider settings remain under `admin/ai-settings.php`.

## Expected output shape

The provider is instructed to return JSON only:

```json
{
  "title": "",
  "logline": "",
  "genre": "",
  "tone": "",
  "visual_style": "",
  "characters": [
    {
      "name": "",
      "role": "",
      "appearance_notes": "",
      "personality_notes": "",
      "wardrobe_notes": "",
      "consistency_prompt": ""
    }
  ],
  "scenes": [
    {
      "scene_number": 1,
      "scene_title": "",
      "scene_summary": "",
      "scene_prompt": "",
      "image_prompt": "",
      "dialog_text": "",
      "action_notes": "",
      "location_label": "",
      "time_of_day": "",
      "characters": ["Character Name"]
    }
  ]
}
```

The parser rejects responses that are not valid JSON or do not include exactly 9 scenes.

## SQL

No new SQL migration was added in Phase 42.

Phase 42 requires migration `021_storyboarding_ai_settings.sql` from Phase 41.

## Runtime requirements

- PHP cURL extension for outbound provider calls.
- Migration 021 installed.
- Active default text provider in `admin/ai-settings.php`.
- Configured provider secret.
- OpenSSL AES-256-GCM support for provider secret storage.

## Recommended next phase

Phase 43 should add scene actions:

- Edit Scene modal persistence.
- Rewrite Scene endpoint.
- Regenerate Image endpoint.
- Upload Image endpoint.
- Character consistency payload builder for image prompts.
- Scene-level job tracking and retry controls.
