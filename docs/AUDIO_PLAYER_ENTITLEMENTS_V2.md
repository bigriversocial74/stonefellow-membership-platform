# Audio Streaming Player v2 + Subscription Enforcement v2

This phase adds the next two streaming-platform layers:

1. Audio Streaming Player v2
2. Subscription Enforcement v2

## Audio Player v2

New/updated files:

- `includes/audio_player.php`
- `api/player-state.php`
- `player.php`
- `album.php`
- `song.php`
- `database/migrations/012_audio_player_entitlements_v2.sql`

### Capabilities

- Signed audio track payloads through the secure media layer.
- Preview/full source mode per track.
- Queue metadata exposed to the existing player JavaScript through `window.STONEFELLOW_TRACKS`.
- Player state API for queue, current song, position, shuffle flag, and repeat mode.
- Album and song pages now use the same signed audio payload layer as the full player.

## Subscription Enforcement v2

Updated file:

- `includes/membership.php`

New files:

- `api/entitlement-check.php`
- `admin/entitlements.php`

### Enforcement behavior

Access levels are ranked:

1. Public
2. Free Account
3. Subscriber
4. Premium
5. Founding Fan
6. Admin

A member can access content when their current access level is equal to or higher than the required level, or when they have a valid direct grant.

### Grace period

Past-due subscriptions can remain active during a configurable grace window.

Set:

```txt
SF_SUBSCRIPTION_GRACE_DAYS=3
```

Default: `3` days.

### Direct grants

Direct grants can override subscription state while valid. Supported examples include:

- `song`
- `album`
- `video`
- `episode`
- `playlist`
- `site_feature`
- `platform`
- `all_access`

## SQL

Run after migration 011:

```txt
database/migrations/012_audio_player_entitlements_v2.sql
```

The web installer has been updated to include migration `012` in the one-click SQL install flow.
