# Mobile PWA Shell + Admin Upload UX v2

This phase adds:

1. Phase 15: Mobile/PWA Offline Media Shell v1
2. Phase 16: Admin Media Upload UX v2

## Added

- `manifest.webmanifest`
- `service-worker.js`
- `offline.php`
- `api/pwa-status.php`
- `admin/mobile-pwa.php`
- `assets/css/pwa-upload.css`
- `assets/js/pwa-upload.js`

## Updated

- `includes/header.php`
- `includes/footer.php`
- `admin/uploads.php`
- `admin/index.php`

## PWA features

- installable app metadata
- Watch, Music, and Library shortcuts
- service worker registration
- core shell cache
- offline fallback page
- mobile install banner
- mobile mini-player helper
- PWA admin status page
- PWA status API

## Upload UX features

- drag/drop upload zone
- preview before upload
- title prefill from filename
- image/audio/video/document validation cards
- media bucket filters
- recent upload cards
- sticky upload action area
- CSRF fields on upload/delete forms
- clearer file assignment guidance

## SQL

No new SQL required.
