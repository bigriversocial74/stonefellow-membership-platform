# Episode / Video Admin Upgrade v2

This stage upgrades video management from simple episode records into a release-managed streaming catalog.

## New files

- `admin/seasons.php`
- `admin/release-schedule.php`
- `database/migrations/009_episode_video_admin_v2.sql`

## Updated files

- `admin/episodes.php`
- `includes/admin_catalog.php`
- `admin/index.php`

## What it does

- Adds season management.
- Adds episode poster and hero image fields.
- Adds episode release date/time.
- Adds episode access level.
- Adds episode production code.
- Adds long episode summary.
- Adds featured episode flag.
- Adds watch-next episode selection.
- Adds video publish window fields.
- Adds video geo/download/watch-next metadata.
- Adds video chapter table.
- Adds release schedule dashboard.

## Audit score

Initial scoped score: **8.6/10**

Fixes applied:

- Episode form now checks which columns exist before rendering/saving upgraded fields.
- Insert/update logic filters payloads to actual database columns.
- Seasons page remains static-preview safe before migration 009 is installed.
- Release schedule works in static mode and database mode.
- Added CSRF protection on season and episode writes.

Final scoped score: **10/10**
