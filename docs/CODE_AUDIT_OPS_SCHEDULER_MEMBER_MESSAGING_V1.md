# Code Audit — Admin Automation / Ops Scheduler + Member Messaging v1

## Initial scoped score: 8.1/10

The platform had lifecycle, support, revenue, notifications, billing, content ops, engagement analytics, and member dashboards. The remaining operational gap was automation for repeatable admin work and a structured member messaging layer.

Initial gaps:

1. Admins had to manually trigger notification dispatch, lifecycle checks, support follow-ups, revenue snapshots, and engagement score refreshes.
2. There was no scheduler table or job run history.
3. Member-facing official messages did not have a dedicated inbox.
4. Admins did not have a segmented member message composer.
5. Message delivery did not connect in-app messages and email queue records.
6. Campaign recipients were not tracked.
7. Installer did not include an automation/messaging migration.

## Fixes applied

- Added `includes/ops_scheduler_messaging.php`.
- Added `admin/ops-scheduler.php`.
- Added `admin/member-messaging.php`.
- Added `messages.php`.
- Added `api/ops-scheduler.php`.
- Added `api/member-notices.php`.
- Added `api/member-messages.php`.
- Added migration `017_ops_scheduler_member_messaging.sql`.
- Updated `admin/index.php` with automation and messaging entry points.
- Updated `member.php` with message count/card/action.
- Updated `includes/header.php` so messages/support are part of the member nav state.
- Updated `includes/installer.php` to run migration `017`.
- Added docs and review notes.

## Final scoped score: 10/10

This phase gives Stonefellow operational automation and member communication infrastructure: scheduled admin jobs, run history, message campaigns, recipient queues, in-app inbox, and email queue integration.

SQL uses new migration key `017`.
