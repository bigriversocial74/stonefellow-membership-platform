# Stonefellow Clip Creator & VP3 Clips Publishing Bridge v1

Stonefellow is the authoritative source for each clip. It owns source-media selection, trim points, aspect ratio, public rendition, poster, caption, destination, rights declaration, scheduling, and the local publication record. VP3 receives only the public rendition/reference required for moderation and discovery.

## Creator workflow

1. Open **Content + Story → VP3 Clips**.
2. Configure the private bridge ID and one-time bridge secret issued for this licensed installation.
3. Select a ready protected video object.
4. Set start/end times, poster time, caption, destination, schedule, and rights owner.
5. Render a separate public MP4 and poster with FFmpeg. The protected master is never exposed.
6. Publish or update the clip through a timestamped, nonced, idempotent HMAC-signed request.
7. Poll moderation, rights, feed status, and network analytics.
8. Withdraw the clip without deleting the source record or historical analytics.

## Security boundaries

- The product license key is never requested, stored, or transmitted by this bridge.
- The bridge secret is encrypted at rest with AES-256-GCM and a dedicated environment key.
- Public media URLs use a random per-clip bearer token and expose only rights-confirmed rendered files.
- The source master remains in protected local/S3 media storage.
- VP3 request IDs are safe to retry only with the same payload.
- Rights confirmation is required before rendering or publishing.

## Environment

```text
SF_VP3_BRIDGE_SETTINGS_KEY=<unique 32+ character secret>
SF_VP3_CLIPS_ROOT=storage/private/vp3_clips
```

The license receipt must contain the installation's public HTTPS base URL.

## Worker

```bash
php jobs/vp3-clips-worker.php 10
```

Schedule the worker through cron or a process supervisor. The admin console can process one bounded job for setup validation.

## SQL

Import after migrations 001–026:

`database/migrations/027_vp3_clips_publisher_bridge.sql`
