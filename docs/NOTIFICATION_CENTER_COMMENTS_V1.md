# Member Notification Center + Comments / Fan Engagement v1

This combined phase adds:

1. Phase 17: Member Notification Center v1
2. Phase 18: Comments / Fan Engagement v1

## Added

- `includes/engagement.php`
- `notifications.php`
- `comments.php`
- `api/notifications.php`
- `api/comments.php`
- `admin/engagement.php`
- `admin/comments.php`

## Updated

- `member.php`
- `admin/index.php`
- `database/migrations/013_gateway_publishing_workflow_v1.sql`

## Member Notification Center

Capabilities:

- member-facing notification center
- unread / read / dismissed states
- mark one notification read
- dismiss one notification
- mark all read
- summary counts
- JSON endpoint for future dropdown/mobile widgets
- fallback to notification logs/static notifications before database install

## Comments / Fan Engagement

Capabilities:

- comment threads for `episode`, `video`, `song`, `album`, `post`, and `product`
- member comment submission
- configurable approval behavior through `SF_COMMENTS_REQUIRE_APPROVAL`
- member reactions: `like`, `love`, `fire`, `laugh`, `wow`
- admin comment moderation queue
- approve / hide / reject / spam actions
- comment moderation audit events
- engagement admin dashboard
- JSON endpoint for future inline content widgets

## SQL

No new installer key was added. Migration `013_gateway_publishing_workflow_v1.sql` was expanded with:

- `member_notifications`
- `fan_comments`
- `fan_reactions`
- `comment_moderation_events`

Fresh installs already run migration `013`, so new installs receive these tables automatically.

Existing installs that already imported migration `013` should manually apply the new table definitions from migration `013`.

## Next recommended phase

After this, the next two phases should be:

1. Creator Posts / News Feed v1
2. Inline Comment Widgets v1
