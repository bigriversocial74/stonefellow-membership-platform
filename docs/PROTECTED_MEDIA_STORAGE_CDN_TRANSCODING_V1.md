# Stonefellow Protected Media Storage, CDN & Transcoding v1

## Certification result

- Initial static score: **6.1/10**
- Final static score: **10/10**
- SQL required: `database/migrations/023_protected_media_storage_cdn_transcoding.sql`

The pre-build platform already had signed URLs, membership entitlement checks, local protected paths, and byte-range streaming. It did not have provider-neutral object storage, resumable uploads, quarantine and whole-file checksums, a leased processing queue, adaptive HLS packaging, waveform data, signed segment sessions, CDN delivery, or operational storage evidence.

## Architecture

The media layer is additive and preserves the existing catalog and signed range-stream path. New protected masters and generated variants are registered in `media_objects` and linked to a song, video, episode, album, series, or standalone asset.

Supported storage drivers:

- `local`: private files under `storage/private_media_v2`, denied from direct web access.
- `s3`: AWS SigV4-compatible object storage, including AWS S3, Cloudflare R2, Wasabi, and MinIO-compatible endpoints.

Credentials are read from environment variables only. Database provider rows contain operational metadata, never access secrets.

## Resumable ingestion

`/admin/media-pipeline.php` uploads files through three authenticated endpoints:

1. `api/media-upload-init.php`
2. `api/media-upload-chunk.php`
3. `api/media-upload-complete.php`

Uploads use bounded chunks, per-chunk SHA-256 checks, whole-file size and SHA-256 verification, MIME/extension allowlists, private staging paths, expiration, duplicate detection, and quarantine before processing.

## Processing jobs

The signed worker endpoint is `api/media-processing-worker.php`. Requests require a five-minute timestamp window and an HMAC over the timestamp and request-body digest.

The queue uses row locks, leases, bounded retries, exponential retry delays, progress, captured output, and terminal failure records. Supported jobs are:

- probe
- integrity check
- audio stream
- 30-second audio preview
- real waveform peak JSON
- adaptive video HLS at source-appropriate 360p, 720p, and 1080p profiles
- video preview
- poster frame
- storage copy

FFmpeg and FFprobe commands are executed as argument arrays through `proc_open`; user input is never concatenated into a shell command.

## Secure delivery

HLS delivery creates short-lived database sessions bound to the authenticated user and, by default, hashed IP and user-agent fingerprints. Master and variant manifests are generated or rewritten so every child manifest and segment receives its own HMAC-signed URL. Segment requests are limited to objects belonging to the active master-manifest tree.

Local objects are served with range support and private cache headers. S3 objects use short-lived SigV4 URLs. An optional CDN base URL can use application HMAC signatures without exposing the origin bucket.

## Operations

The admin pipeline provides:

- storage and binary readiness
- storage usage by media role
- resumable upload progress
- queue state and retries
- object primary selection
- signed health checks that write, read, and delete a test object
- active delivery sessions and delivered segment counts
- expired upload cleanup

## Configuration

Copy the required entries from `.env.media.example` into the hosting environment. At minimum configure:

- `SF_MEDIA_SIGNING_KEY`
- `SF_MEDIA_DELIVERY_SESSION_SECRET`
- `SF_MEDIA_WORKER_SECRET`
- `SF_FFMPEG_PATH`
- `SF_FFPROBE_PATH`

For S3-compatible storage, also configure the access key, secret, bucket, region, endpoint, and path-style mode. For CDN delivery, configure the CDN base URL and signing key.

## Operational boundary

The 10/10 result is a source-code and static-control score. Operational certification still requires:

- importing migration 023
- confirming the private local path or S3 bucket permissions
- running a successful write/read/delete health check
- processing real audio and video masters with the server’s FFmpeg build
- confirming waveform, preview, poster, and HLS outputs
- testing HLS in Safari and an HLS.js-capable browser
- validating member, preview, direct-grant, expired-session, cross-user, and revoked-session behavior
- load testing concurrent segment delivery and processing workers
- verifying CDN cache/origin protection when a CDN is enabled
