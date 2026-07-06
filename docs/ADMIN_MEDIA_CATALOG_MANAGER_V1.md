# Stonefellow Admin Media Catalog Manager v1

This package adds the first database-backed admin manager for the Stonefellow membership site.

## Routes added

- `admin/music.php` — media catalog dashboard and build workflow status.
- `admin/music-albums.php` — album create/edit/delete, cover asset assignment, publish/archive status.
- `admin/music-songs.php` — song create/edit/delete, album assignment, access level, featured state, audio file variants.
- `admin/episodes.php` — episode create/edit/delete, season/episode numbering, runtime, publish/archive status.
- `admin/videos.php` — video create/edit/delete, episode assignment, access level, poster asset, video file variants.
- `admin/media-access.php` — subscription plan management and direct member content grants.
- `admin/uploads.php` — media asset registry for image/audio/video/document paths.

## Shared admin helper

- `includes/admin_catalog.php`

The helper centralizes:

- database-safe fetch/execute wrappers
- static catalog fallback mode
- CRUD flash messages
- admin audit logging when `admin_audit_log` exists
- slug generation
- table/column existence checks
- admin shell/sidebar rendering

## Database behavior

The admin manager is safe to open before the database is configured. In no-database mode it:

- shows static/demo data from `includes/data.php`
- displays a database warning banner
- disables save/delete submit buttons

When the database is configured with `SF_DB_HOST`, `SF_DB_NAME`, `SF_DB_USER`, and `SF_DB_PASS`, the same pages save to MySQL.

## SQL dependency order

Apply SQL in this order:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`

Admin v1 does not require a new migration. It uses the tables that already exist in the base SQL and migrations 001/002.

## Tables managed

### Music

- `albums`
- `songs`
- `song_files`
- `media_assets`

### Series/video

- `episodes`
- `videos`
- `video_files`
- `media_assets`

### Membership/access

- `subscription_plans`
- `content_access_grants`
- `users` for member selection
- `admin_audit_log` for create/update/delete logs when migration 001 is applied

## Current boundary

Admin v1 registers paths; it does not yet move uploaded files. The next phase should add real file uploads with:

- MIME validation
- file size limits
- allowed extensions
- local or CDN storage adapters
- admin-only authentication enforcement
- CSRF tokens
