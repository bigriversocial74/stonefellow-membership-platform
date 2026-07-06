# Email + Notification Runtime v1

This phase adds a production-safe transactional notification layer for the Stonefellow membership site.

## Files added

- `includes/notifications.php`
- `admin/notifications.php`
- `admin/email-templates.php`
- `api/notification-webhook.php`
- `database/migrations/006_email_notifications.sql`
- `docs/EMAIL_NOTIFICATION_RUNTIME_V1.md`

## Database tables

Migration `006_email_notifications.sql` adds:

- `email_templates`
- `notification_logs`
- `notification_preferences`
- `notification_webhook_events`

Run order:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`
4. `database/migrations/003_media_upload_storage_metadata.sql`
5. `database/migrations/004_billing_entitlements.sql`
6. `database/migrations/005_merch_order_runtime.sql`
7. `database/migrations/006_email_notifications.sql`

## Built notification events

The runtime now logs and dispatches:

- signup welcome email
- password reset email
- subscription started email
- subscription canceled email
- payment receipt email
- merch order confirmation email
- order fulfilled email
- admin new order alert
- admin failed payment alert foundation
- playlist share notification foundation

## Provider modes

Default mode is safe sandbox/log mode:

```txt
SF_MAIL_PROVIDER=log
```

This records messages in `notification_logs` and marks them as sent without sending real mail.

Optional PHP mail handoff:

```txt
SF_MAIL_PROVIDER=mail
SF_MAIL_FROM_EMAIL=support@stonefellow.tv
SF_MAIL_FROM_NAME=Stonefellow
SF_PUBLIC_URL=https://stonefellow.tv
SF_ADMIN_EMAILS=admin@example.com,orders@example.com
```

Production gateway adapters such as SendGrid, Postmark, Mailgun, Amazon SES, or SMTP can be added behind `sf_notify_transport_send()`.

## Admin pages

### `admin/notifications.php`

Controls:

- provider/database status
- queue metrics
- seed default templates
- dispatch pending queue
- send template test
- recent notification log
- resend failed/queued notification

### `admin/email-templates.php`

Controls:

- template list
- edit template key/name/category/status
- edit subject
- edit HTML body
- edit text body
- edit variable JSON
- preview rendered sample

## Webhook endpoint

`api/notification-webhook.php` accepts provider events and writes them into `notification_webhook_events`.

It can update a matching `notification_logs` row when the payload includes a provider message id:

```json
{
  "provider": "sendgrid",
  "event_type": "delivered",
  "event_id": "evt_123",
  "provider_message_id": "msg_123"
}
```

## Integration points

The runtime is wired into these flows:

- `sf_auth_register()` sends `welcome`
- `sf_password_reset_create()` sends `password_reset`
- `sf_billing_complete_checkout()` sends `subscription_started` and `payment_receipt`
- `sf_billing_cancel_subscription()` sends `subscription_canceled`
- `admin/members.php` sends subscription assignment/cancel notifications
- `sf_store_create_order()` sends `merch_order_confirmation` and `admin_new_order`
- `sf_store_update_order_status()` sends `order_fulfilled` when status becomes fulfilled

## Static/no-database behavior

Without DB credentials, the system still loads safely. Messages are written to a session preview log. Admin save buttons are disabled until database mode is active.

## Next production work

- add SMTP or API provider adapter
- add unsubscribe/preference UI
- add order shipped tracking template
- add queued cron runner route or CLI command
- add DKIM/SPF/domain verification checklist
