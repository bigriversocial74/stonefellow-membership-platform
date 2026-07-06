# Notification Center + Comments Review Notes

Branch: `feature/notifications-comments-v1`

Post-merge checks:

1. Open `member.php` and confirm notification/comment cards render.
2. Open `notifications.php` as a signed-in member.
3. Open `comments.php` and submit a test comment.
4. Open `admin/comments.php` and approve/hide/reject a comment.
5. Open `admin/engagement.php`.
6. Open `api/notifications.php` as a signed-in member.
7. Open `api/comments.php?content_type=episode`.
8. Confirm migration `013` contains `member_notifications`, `fan_comments`, `fan_reactions`, and `comment_moderation_events`.

SQL: no new installer key. Existing installs that already imported migration `013` should manually apply the new table definitions.
