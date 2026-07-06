# Content Import + Seed Manager v1

This phase adds a database-backed import and seed manager for the Stonefellow membership platform.

## New files

- `includes/importer.php` — shared CSV/JSON parsing, validation, upsert, seed, import history, and rollback helpers.
- `admin/import.php` — upload/paste CSV or JSON rows, preview normalized payloads, and commit validated imports.
- `admin/seed-manager.php` — run starter catalog seeds, view recent import batches, inspect row logs, and roll back imported rows.
- `admin/demo-content.php` — sample JSON rows for every supported content type and starter seed payload visibility.
- `database/seeds/starter_catalog.json` — checked-in starter seed payload for review/reference.
- `database/migrations/011_content_import_seed_manager.sql` — import batch and row audit/rollback tables.

## Supported import types

- `media_asset`
- `product_category`
- `album`
- `episode`
- `song`
- `song_file`
- `video`
- `video_file`
- `subscription_plan`
- `product`
- `product_variant`

## Workflow

1. Run the base SQL and migrations `001` through `011` in numeric order.
2. Open `admin/import.php`.
3. Choose an import type.
4. Upload CSV/JSON or paste a JSON array.
5. Preview normalized payloads and validation errors.
6. Commit only after the preview shows all rows ready.
7. Review the import batch in `admin/seed-manager.php`.
8. Roll back a batch if needed.

## Upsert keys

Imports are idempotent by normalized natural keys:

| Type | Upsert key |
|---|---|
| `media_asset` | `file_path` |
| `product_category` | `slug` |
| `album` | `slug` |
| `episode` | `slug` |
| `song` | `slug` |
| `song_file` | `song_id + file_type + file_path` |
| `video` | `slug` |
| `video_file` | `video_id + file_type + file_path` |
| `subscription_plan` | `slug` |
| `product` | `slug` |
| `product_variant` | `product_id + variant_name` |

## Relation helpers

Imports can reference related records by slug/path instead of numeric IDs:

- `album_slug` resolves to `album_id` for songs.
- `song_slug` resolves to `song_id` for song files.
- `episode_slug` resolves to `episode_id` for videos.
- `video_slug` resolves to `video_id` for video files.
- `product_slug` resolves to `product_id` for product variants.
- `category_slug` or `category_name` resolves to `category_id` for products.

## Rollback behavior

Each committed row stores source JSON, target table, target record ID, action, status, before snapshot for updated rows, and after snapshot for inserted/updated rows.

Rolling back a batch removes inserted rows and restores updated rows using the stored `before_json` snapshot. Skipped rows are left unchanged.

## Notes

- The importer requires a database connection and migration `011`.
- Static/no-database preview mode keeps the pages visible but prevents real imports.
- CSV headers should match the same field names used in JSON samples.
- Price aliases like `price` and `compare_at_price` are converted to `*_cents` values.
- Boolean values accept `1`, `yes`, `true`, `on`, `published`, `active`, or `featured`.
