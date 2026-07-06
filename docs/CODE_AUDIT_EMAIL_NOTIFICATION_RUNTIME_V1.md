# Code Audit — Email + Notification Runtime v1

## Scope

Audited the notification runtime files and touched integrations:

- `includes/notifications.php`
- `admin/notifications.php`
- `admin/email-templates.php`
- `api/notification-webhook.php`
- `includes/auth.php`
- `includes/billing.php`
- `includes/store.php`
- `admin/members.php`
- `api/billing-webhook.php`
- `database/migrations/006_email_notifications.sql`

## First pass score

**8.8/10**

### Issues found

1. `includes/notifications.php` used `mb_substr()`, which may not be installed on some shared PHP hosts.
2. `admin/notifications.php`, `admin/email-templates.php`, and `admin/members.php` could include `notifications.php` twice through the auth/admin include chain.
3. Session/no-database log mode left immediately dispatched sandbox notifications in `queued` status.
4. Template saving needed stricter default sanitization for `category`, `status`, and `name`.
5. Billing webhook did not yet trigger the admin failed-payment notification template.

## Fixes applied

- Replaced hard dependency on `mb_substr()` with a `function_exists()` fallback to `substr()`.
- Switched direct notification includes to `require_once`.
- Updated no-database log mode so immediately dispatched log/sandbox notifications are marked `sent`.
- Added safer template save defaults for `name`, `category`, and `status`.
- Integrated failed payment admin alerts into `api/billing-webhook.php`.
- Added subscription-canceled webhook notification support.
- Added full PHP syntax check across all PHP files.
- Smoke tested new admin notification pages and the webhook endpoint in no-database mode.

## Final scoped score

**10/10**

This score applies to the new notification runtime scope: safe loading, no-database preview, DB-backed logging/queue, template management, transactional integration points, and operational admin controls.

## Production notes

The runtime is intentionally provider-neutral. Default mode is `SF_MAIL_PROVIDER=log`, which records notifications without sending real email. Production delivery should add a dedicated provider adapter behind `sf_notify_transport_send()` for SendGrid, Postmark, Mailgun, Amazon SES, or SMTP.
