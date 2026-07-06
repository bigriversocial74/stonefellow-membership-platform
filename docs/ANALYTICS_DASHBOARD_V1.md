# Analytics Dashboard v1

This phase adds the first admin analytics section for the Stonefellow membership platform.

## New admin pages

- `admin/analytics.php` — top-level performance overview for audio, video, members, playlists, subscriptions, and merch revenue.
- `admin/audio-analytics.php` — song-level audio report with plays, listeners, seconds streamed, completion events, average percent complete, and recent tracking events.
- `admin/video-analytics.php` — video/episode watch report with watch events, viewers, watch time, completion events, average percent complete, and recent tracking events.
- `admin/member-activity.php` — member engagement table showing account status, subscription plan, recent logins, playlist count, audio engagement, and video engagement.

## New shared include

- `includes/admin_analytics.php`

This file contains analytics helpers for:

- date range selection
- metric cards
- duration/money/percentage formatting
- audio/video/member summary queries
- top song and top video queries
- recent tracking event tables
- daily activity bars
- static/no-database fallback behavior

## Database tables used

The dashboard reads from existing tables only. No new migration is required.

Primary tracking tables:

- `audio_play_events`
- `user_song_progress`
- `video_watch_events`
- `user_video_progress`
- `user_episode_progress`

Member and access tables:

- `users`
- `user_subscriptions`
- `subscription_plans`
- `playlists`

Catalog tables:

- `songs`
- `albums`
- `videos`
- `episodes`

Commerce signal tables:

- `orders`
- `order_items`

## Date range filters

Each analytics page supports:

- `?days=7`
- `?days=30`
- `?days=90`
- `?days=365`

Example:

```txt
admin/analytics.php?days=30
```

## Static preview behavior

Without database credentials, analytics pages remain viewable in static/admin preview mode. The reports show catalog fallback rows and zeroed tracking counts until the database and tracking API writes are enabled.

## Runtime flow

1. Member uses the music player or watch page.
2. Front-end JavaScript posts tracking payloads to `api/audio-track.php` or `api/video-track.php`.
3. APIs write event rows and update member progress rows when a signed-in user exists.
4. Analytics pages read those tables and summarize performance.

## Next recommended analytics phase

Analytics v2 should add export and rollups:

- CSV export for audio, video, and member activity.
- Daily/weekly rollup tables for faster long-range reporting.
- Conversion tracking from public preview to signup/subscription.
- Email campaign segments for inactive paid members, high-value listeners, episode completers, and playlist creators.
