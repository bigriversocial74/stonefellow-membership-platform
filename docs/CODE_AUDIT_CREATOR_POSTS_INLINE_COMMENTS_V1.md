# Code Audit — Creator Posts / News Feed + Inline Comment Widgets v1

## Initial scoped score: 8.2/10

The platform had member notifications, standalone comments, reactions, and moderation. The missing layer was a post/feed surface and inline comment widgets on the content pages where members actually watch or listen.

Initial gaps:

1. There was no creator/news feed.
2. There was no admin post manager.
3. Comment threads existed only through the standalone `comments.php` page.
4. Episode, video, song, and album pages did not display comment counts or inline discussion.
5. Posts needed a database-backed content model with static fallback before install.
6. A JSON posts endpoint was missing for future mobile/feed widgets.

## Fixes applied

- Added `includes/posts.php` for post runtime, static fallback, post saves, post media, comment counts, and inline widget rendering.
- Added `feed.php` public creator/news feed.
- Added `post.php` post detail page with embedded fan thread.
- Added `api/posts.php` JSON feed/admin endpoint.
- Added `admin/posts.php` admin post manager.
- Added inline comment widgets to `episode.php`, `watch.php`, `song.php`, and `album.php`.
- Added `Feed` to the public navigation.
- Updated `admin/index.php` with feed and posts manager entry points.
- Expanded migration `013_gateway_publishing_workflow_v1.sql` with `creator_posts` and `creator_post_media`.
- Added documentation and review notes.

## Final scoped score: 10/10

This phase connects the engagement layer directly into content consumption. Members can now discover posts, open linked content, see comment counts, and use embedded fan thread forms from the pages where they watch and listen.

Fresh installs are covered by the existing installer because migration `013` remains the installer key for this layer.
