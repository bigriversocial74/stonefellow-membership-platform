# Likenessing Platform Reskin

This branch reskins the existing Stonefellow streaming and membership platform as Likenessing while preserving the application runtime.

## Scope
- Shared public/member header and footer branding
- Full Likenessing homepage
- About, Cast, Extras, and News pages
- Global non-admin visual reskin for episodes, playback, authentication, memberships, account, commerce, library, support, and other existing surfaces
- Raster-only image assets (PNG/WebP); no SVG files
- Likenessing PWA manifest, cache, icons, offline screen, and metadata
- Admin functionality remains on the original admin presentation and is intentionally excluded from public theme CSS

## Logic boundary
No database schema, authentication, entitlement, signed playback, watch progress, comments, commerce, installer, licensing, admin, or API logic is removed.

## SQL
No SQL required.
