# Member Library + Search Discovery v1

This combined phase adds the next two streaming-platform layers:

1. Member Library + Watchlist v1
2. Search + Discovery v1

## Member Library + Watchlist v1

New/updated files:

- `includes/library.php`
- `library.php`
- `watchlist.php`
- `api/member-library.php`
- `member.php`

### Capabilities

- Saved content collection for songs, albums, videos, episodes, playlists, and products.
- Watchlist queue for episodes and videos.
- Liked and completed states.
- Progress fields for resume-aware library cards.
- JSON API for reading, saving, and removing library records.
- Static fallback items when the database is not configured.

## Search + Discovery v1

New files:

- `includes/search.php`
- `search.php`
- `api/search.php`
- `admin/search-discovery.php`

### Capabilities

- Unified search across songs, videos, episodes, albums, and merch.
- Search facets by content type.
- Public search page.
- JSON search API for future autocomplete/mobile clients.
- Admin coverage diagnostics for searchable catalog records.
- Static fallback search index when the database is not configured.

## SQL

The member library and search discovery tables are included in the expanded migration:

```txt
database/migrations/013_gateway_publishing_workflow_v1.sql
```

This keeps the current installer coverage intact because the installer already runs migration `013`.

Tables added through the expanded migration:

- `member_library_items`
- `content_search_index`

## Next recommended phase

After this, the next two phases should be:

1. Streaming Analytics v2
2. Production Deployment Package v1
