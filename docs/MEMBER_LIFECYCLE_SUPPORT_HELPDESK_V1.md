# Admin Member Lifecycle / Retention v1 + Customer Support Help Desk v1

This combined phase adds:

1. Phase 25: Admin Member Lifecycle / Retention v1
2. Phase 26: Customer Support + Help Desk v1

## Added

- `includes/member_lifecycle_support.php`
- `admin/member-lifecycle.php`
- `admin/support.php`
- `support.php`
- `api/member-lifecycle.php`
- `api/support-tickets.php`
- `database/migrations/016_member_lifecycle_support_helpdesk.sql`

## Updated

- `admin/index.php`
- `includes/footer.php`
- `includes/installer.php`

## Member lifecycle / retention

Capabilities:

- admin lifecycle dashboard
- subscriber segment list
- active/trialing/free/churn-risk totals
- grace-period / churn-risk detection
- open support ticket count per member
- open retention task count per member
- selected member lifecycle profile
- admin lifecycle notes
- admin retention tasks
- task status updates
- JSON lifecycle API

## Customer support / help desk

Capabilities:

- member-facing support center
- member ticket creation
- member ticket history
- member ticket replies
- admin support inbox
- ticket statuses: new, open, pending member, pending admin, resolved, closed
- ticket categories: account, billing, technical, content, merch, feedback, other
- ticket priorities: low, medium, high, urgent
- admin replies
- internal admin notes
- ticket context fields for subscriptions, orders, invoices, and content
- JSON support tickets API

## SQL

New migration:

```txt
database/migrations/016_member_lifecycle_support_helpdesk.sql
```

Creates:

- `member_lifecycle_notes`
- `member_retention_tasks`
- `support_tickets`
- `support_ticket_messages`
- `support_ticket_events`

Installer now runs migrations through `016`.

## Existing installs

Apply migration `016` after migration `015`.
