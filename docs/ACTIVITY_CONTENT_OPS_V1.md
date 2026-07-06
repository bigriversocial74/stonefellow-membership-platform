# Notifications v2 / Member Activity Feed + Creator/Admin Content Ops v1

This combined phase adds the next two daily-operations layers:

1. Phase 13: Notifications v2 / Member Activity Feed
2. Phase 14: Creator/Admin Content Ops Dashboard

## Added

- `includes/activity_ops.php`
- `admin/activity-feed.php`
- `admin/content-ops.php`
- `api/activity-feed.php`
- `api/ops-summary.php`

## Updated

- `admin/index.php`
- `database/migrations/013_gateway_publishing_workflow_v1.sql`

## Activity Feed

The activity feed gives admins a timeline of platform activity:

- member signups
- stream/watch/play events
- library saves and watchlist activity
- orders and purchases
- notification status
- payment status
- publishing changes
- admin/system activity

The runtime uses the `member_activity_events` table when installed and falls back to notification/static catalog events before installation.

## Content Ops Dashboard

The content ops dashboard gives admins a daily command center for:

- failed notifications
- queued notifications
- open merch orders
- draft content
- scheduled releases
- published videos missing metadata
- payment gateway readiness
- library engagement
- recent members and orders

## APIs

```txt
api/activity-feed.php
api/ops-summary.php
```

The APIs are intended for future dashboard widgets, mobile admin tools, and notification/event automation.

## SQL

No new installer key was added. Migration `013_gateway_publishing_workflow_v1.sql` was expanded to include:

- `member_activity_events`
- `content_ops_tasks`

Fresh installs already run migration `013`, so the installer receives these tables automatically.

## Next recommended phase

After this, the next two phases should be:

1. Mobile/PWA Offline Media Shell v1
2. Admin Media Upload UX v2
