# Creator Posts + Inline Comments Review Notes

Branch: `feature/posts-inline-comments-v1`

Post-merge checks:

1. Open `feed.php` and confirm post cards render.
2. Open `post.php?slug=behind-the-first-chapter`.
3. Open `admin/posts.php` and create/edit a post.
4. Open `api/posts.php`.
5. Open `episode.php`, `watch.php`, `song.php`, and `album.php` and confirm inline comment widgets render.
6. Submit a quick comment from an inline widget as a signed-in member.
7. Moderate that comment from `admin/comments.php`.
8. Confirm migration `013` contains `creator_posts` and `creator_post_media`.

SQL: no new installer key. Existing installs that already imported migration `013` should manually apply the new post table definitions.
