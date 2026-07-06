# Billing + Subscriptions v1

This phase turns the membership plan selection into a real checkout and entitlement activation workflow.

## What was added

Public/member pages:

- `billing-checkout.php` — authenticated checkout session page
- `billing-success.php` — post-checkout confirmation page
- `billing-cancel.php` — canceled checkout page
- `account-billing.php` — member subscription, invoice, transaction, and cancellation page

Admin/API files:

- `admin/billing.php` — billing control dashboard
- `api/billing-webhook.php` — webhook receiver for future Stripe/payment processor events
- `includes/billing.php` — checkout, invoice, payment transaction, subscription, and entitlement helpers
- `database/migrations/004_billing_entitlements.sql` — billing tables and subscription metadata

## Runtime flow

1. Visitor chooses a plan on `subscribe.php`.
2. If not signed in, the site sends them to signup/signin.
3. A pending record is created in `subscription_checkouts`.
4. User completes `billing-checkout.php`.
5. Sandbox checkout records:
   - active `user_subscriptions` row
   - paid `invoices` row
   - paid `payment_transactions` row
   - completed `subscription_checkouts` row
   - subscription-based `content_access_grants`
6. Member access gates unlock audio, video, playlists, and episode tracking based on subscription status.

## Sandbox mode

By default, `SF_PAYMENT_PROVIDER` is `sandbox`.

Sandbox mode does not charge a real card. It creates production-shaped records so the membership site can be tested end-to-end before Stripe or another payment processor is connected.

## Production processor path

When ready for production:

1. Add processor price IDs to `subscription_plans.processor_price_id`.
2. Replace the sandbox form in `billing-checkout.php` with Stripe Checkout or Elements.
3. Send successful provider events to `api/billing-webhook.php`.
4. Set `SF_BILLING_WEBHOOK_SECRET` and verify signatures.
5. Update `includes/billing.php` provider mapping if needed.

## Required SQL order

Run:

1. `database/stonefellow_streaming_platform.sql`
2. `database/migrations/001_membership_video_tracking.sql`
3. `database/migrations/002_video_playlist_runtime_seed.sql`
4. `database/migrations/003_media_upload_storage_metadata.sql`
5. `database/migrations/004_billing_entitlements.sql`

## New tables

- `billing_customers`
- `subscription_checkouts`
- `invoices`
- `payment_transactions`
- `billing_webhook_events`

## Updated tables

- `subscription_plans`
- `user_subscriptions`

## Next build recommendation

Build **Merch Cart + Order Runtime v1** next so checkout, products, cart items, order records, payment transactions, and subscriber-only merch can use the same billing foundation.
