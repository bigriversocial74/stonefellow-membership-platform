# Creator Posts / News Feed + Inline Comment Widgets v1

This combined phase adds:

1. Phase 19: Creator Posts / News Feed v1
2. Phase 20: Inline Comment Widgets v1

## Added

- `includes/posts.php`
- `feed.php`
- `post.php`
- `api/posts.php`
- `admin/posts.php`

## Updated

- `episode.php`
- `watch.php`
- `song.php`
- `album.php`
- `includes/header.php`
- `admin/index.php`
- `database/migrations/013_gateway_publishing_workflow_v1.sql`

## Creator Posts / News Feed

Capabilities:

- public creator/news feed
- post detail pages
- admin post manager
- post type support for news, episode, music, merch, and behind-scenes posts
- draft / scheduled / published / archived states
- featured flag
- linked content type/id/slug
- post image path
- post media table foundation
- JSON posts API
- static fallback posts before database install

## Inline Comment Widgets

Inline widgets were added to:

- episode pages
- watch/video pages
- song pages
- album pages
- post pages

Widget behavior:

- comment count badge
- latest approved comments
- quick comment form for signed-in members
- sign-in gate for guests
- link to full `comments.php` thread
- moderation remains handled by `admin/comments.php`

## SQL

No new installer key was added. Migration `013_gateway_publishing_workflow_v1.sql` was expanded with:

- `creator_posts`
- `creator_post_media`

Fresh installs already run migration `013`, so new installs receive these tables automatically.

Existing installs that already imported migration `013` should manually apply the new table definitions from migration `013`.

## Next recommended phase

After this, the next two phases should be:

1. Feed Personalization / Follow System v1
2. Member Engagement Analytics v2
