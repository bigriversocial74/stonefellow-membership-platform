# SQL Note

This phase adds a new migration key.

New migration:

```txt
database/migrations/016_member_lifecycle_support_helpdesk.sql
```

Tables:

- `member_lifecycle_notes`
- `member_retention_tasks`
- `support_tickets`
- `support_ticket_messages`
- `support_ticket_events`

Installer:

- `includes/installer.php` now runs migrations through `016`.

Existing installs:

- Apply migration `016` after migration `015`.
