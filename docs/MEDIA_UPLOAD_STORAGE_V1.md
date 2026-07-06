# Media Upload + Storage v1

This phase turns the earlier media path registry into a working upload/storage layer for the Stonefellow membership site.

## What was added

### Admin upload manager

`admin/uploads.php` now supports two workflows:

1. **Upload a local file** into the app asset folders.
2. **Register an existing path or CDN URL** without uploading a file.

The upload manager previews assets directly in the admin table:

- image previews for covers/posters
- audio controls for song files
- video controls for episode/trailer files
- document links for supporting files

### Upload folders

Uploaded files are stored by media type and date:

```txt
assets/images/uploads/YYYY/MM/
assets/audio/uploads/YYYY/MM/
assets/video/uploads/YYYY/MM/
assets/documents/uploads/YYYY/MM/
```

The package includes README files inside each upload folder so the directories exist in the ZIP.

### Validation

Uploads are validated before they are moved into the asset folders.

| Type | Max Size | Allowed Extensions |
|---|---:|---|
| Image | 12 MB | jpg, jpeg, png, webp, gif |
| Audio | 120 MB | mp3, wav, m4a, aac, ogg, flac |
| Video | 800 MB | mp4, webm, mov, m4v |
| Document | 30 MB | pdf, txt, doc, docx |

The upload handler also checks MIME type, generates a safe unique filename, stores file size, stores MIME type, and stores a SHA-256 checksum when migration 003 has been applied.

## Catalog picker wiring

The catalog manager now uses uploaded assets directly:

- `admin/music-albums.php` previews selected album cover assets.
- `admin/music-songs.php` previews selected song cover assets and lets admins choose uploaded audio assets when adding song file variants.
- `admin/videos.php` previews selected poster assets and lets admins choose uploaded video assets when adding video file variants.

Admins can still paste a manual path for local files or CDN-hosted files.

## SQL migration

New migration:

```txt
database/migrations/003_media_upload_storage_metadata.sql
```

This migration adds optional metadata columns to `media_assets`:

- `original_filename`
- `mime_type`
- `file_size_bytes`
- `checksum_sha256`
- `storage_disk`
- `uploaded_by_user_id`
- `updated_at`

The PHP layer is backward-compatible: if these columns do not exist yet, uploads and manual registrations still use the original `title`, `file_path`, `file_type`, `alt_text`, and `usage_key` fields.

## Production storage note

Local uploads are fine for staging and smaller launch testing. For production video streaming, keep using the media asset registry as the control layer, but point video file paths to a CDN or video platform such as Bunny, S3/CloudFront, Mux, Vimeo OTT, or similar.
