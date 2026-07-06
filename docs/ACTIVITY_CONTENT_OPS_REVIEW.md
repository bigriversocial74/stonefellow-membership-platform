# Activity + Content Ops Review Notes

Branch: `feature/activity-content-ops-v1`

Post-merge checks:

1. Open `admin/index.php` and confirm Activity + Content Ops cards render.
2. Open `admin/activity-feed.php`.
3. Open `admin/content-ops.php`.
4. Open `api/activity-feed.php` while signed in.
5. Open `api/ops-summary.php` as admin.
6. Confirm migration `013` contains `member_activity_events` and `content_ops_tasks` for fresh installs.

SQL: migration `013` was expanded; no new installer key required.
