# Secure Media Delivery + Video Player Upgrade v1

This combined phase adds the next two streaming-platform phases:

1. Secure Media Delivery + Entitlement Gate v1
2. Video Player Upgrade v1

## New delivery layer

New files:

- `includes/media_delivery.php`
- `stream.php`
- `download.php`
- `api/media-token.php`
- `admin/media-delivery.php`
- `storage/private_media/README.md`
- `storage/private_media/.htaccess`

## What it does

- Generates short-lived signed URLs for video and song files.
- Validates URL signature and expiration before serving media.
- Rechecks membership access and direct grants at request time.
- Serves media through `stream.php` with byte-range support for video/audio seeking.
- Adds `download.php` for signed attachment downloads.
- Adds `api/media-token.php` for POST-based token renewal or player integrations.
- Adds an admin delivery dashboard for source paths, signed URLs, and protection status.

## Access model

The delivery layer allows access when any of these are true:

- file type is `preview`
- content access level is `public`
- current member access rank satisfies the content access level
- user has a direct grant for the video/song/episode
- user is admin

The delivery layer blocks the request if the token is expired, the signature is invalid, the content is missing, the source file is missing, or the user does not have access.

## Signed URL format

Signed URLs are generated through:

```php
sf_media_signed_url('video', $videoId, 'stream', 'stream', 900);
sf_media_signed_url('song', $songId, 'full', 'download', 900);
```

The HMAC signature uses:

- `SF_MEDIA_SIGNING_KEY`, when set
- otherwise `SF_HASH_SALT`, when set
- otherwise a hash of `storage/install.lock`, when installed
- otherwise a local development fallback key

For production, set `SF_MEDIA_SIGNING_KEY` or `SF_HASH_SALT`.

## Video player upgrades

`watch.php` now uses the secure delivery helper instead of direct static MP4 paths. It also adds:

- signed playback source status
- preview/full mode label
- next episode card
- chapter marker section
- stronger source-needed messaging
- secure delivery admin link
- existing progress tracking remains wired through `api/video-track.php`

## Protected media storage

Preferred protected storage path:

```txt
storage/private_media/
```

The stream helper checks `storage/private_media/` first, then falls back to `assets/` for compatibility with the current catalog paths.

For Apache hosts, `.htaccess` blocks directory indexing inside `storage/private_media/`. For a stronger production setup, place full files outside the public web root or add server-level deny rules for full media folders.

## Production notes

- Public previews and trailers can remain in public asset folders.
- Full subscriber audio/video should move to `storage/private_media/` or a protected server folder.
- The old direct paths can remain in the catalog as logical file paths because the stream helper resolves them securely.
- Signed URLs expire after 15 minutes by default.
- If a CDN is added later, this layer can become the origin signer.
