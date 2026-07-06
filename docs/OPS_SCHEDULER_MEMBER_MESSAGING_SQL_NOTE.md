# SQL Note

This phase adds a new migration key.

New migration:

```txt
database/migrations/017_ops_scheduler_member_messaging.sql
```

Tables:

- `ops_scheduled_jobs`
- `ops_job_runs`
- `member_message_threads`
- `member_messages`
- `member_message_campaigns`
- `member_message_recipients`

Seeded records:

- default scheduler jobs
- `member_message_notice` email template

Installer:

- `includes/installer.php` now runs migrations through `017`.

Existing installs:

- Apply migration `017` after migration `016`.
