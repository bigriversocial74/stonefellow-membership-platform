# Admin Automation / Ops Scheduler v1 + Member Messaging / Campaign Broadcasts v1

This combined phase adds:

1. Phase 27: Admin Automation / Ops Scheduler v1
2. Phase 28: Member Messaging + Campaign Broadcasts v1

## Added

- `includes/ops_scheduler_messaging.php`
- `admin/ops-scheduler.php`
- `admin/member-messaging.php`
- `messages.php`
- `api/ops-scheduler.php`
- `api/member-notices.php`
- `api/member-messages.php`
- `database/migrations/017_ops_scheduler_member_messaging.sql`

## Updated

- `admin/index.php`
- `member.php`
- `includes/header.php`
- `includes/installer.php`

## Admin Automation / Ops Scheduler v1

Capabilities:

- scheduled admin jobs
- due-job runner
- manual run button per job
- run history table
- notification dispatch job
- lifecycle churn-risk scan job
- support SLA scan job
- revenue snapshot job
- engagement score refresh job
- custom/manual job logging
- scheduler JSON API

## Member Messaging + Campaign Broadcasts v1

Capabilities:

- admin message campaign composer
- audience filters: all members, active subscribers, free members, churn risk, engaged members, support-open members, manual queue
- email channel using existing notification queue
- in-app member message channel
- member-facing message inbox
- message read/archive/dismiss workflow
- recipient queue tracking
- campaign delivery status tracking
- member notices JSON API
- member messages JSON API

## SQL

New migration:

```txt
database/migrations/017_ops_scheduler_member_messaging.sql
```

Creates:

- `ops_scheduled_jobs`
- `ops_job_runs`
- `member_message_threads`
- `member_messages`
- `member_message_campaigns`
- `member_message_recipients`

It also seeds:

- default scheduler jobs
- `member_message_notice` email template

Installer now runs migrations through `017`.

## Existing installs

Apply migration `017` after migration `016`.
