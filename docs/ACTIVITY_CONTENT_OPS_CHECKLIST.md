# Activity + Content Ops Checklist

Post-merge smoke test:

- `admin/activity-feed.php`
- `admin/content-ops.php`
- `api/activity-feed.php`
- `api/ops-summary.php`
- `admin/index.php`

Fresh install SQL:

- Confirm migration `013` creates `member_activity_events`.
- Confirm migration `013` creates `content_ops_tasks`.
