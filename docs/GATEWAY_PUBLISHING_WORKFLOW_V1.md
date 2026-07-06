# Gateway Production Pass + Publishing Workflow v1

This combined phase adds the next two streaming-platform layers:

1. Live Payment Gateway Production Pass v1
2. Content Publishing Workflow v1

## Gateway Production Pass v1

Updated/added files:

- `includes/payment_gateway.php`
- `includes/billing_provider_runtime.php`
- `api/payment-webhook.php`
- `admin/payment-gateways.php`
- `billing-checkout.php`

### Capabilities

- Stripe Checkout Session creation through direct HTTPS API calls when `SF_STRIPE_SECRET_KEY` is configured.
- Provider checkout handoff from `billing-checkout.php`.
- Stripe webhook signature verification using `SF_STRIPE_WEBHOOK_SECRET`.
- Provider event lifecycle handling for:
  - `checkout.session.completed`
  - `customer.subscription.deleted`
  - `customer.subscription.paused`
  - `invoice.payment_failed`
- Provider checkout activation without requiring a logged-in session.
- Paid invoice, payment transaction, active subscription, and content access grant creation from a verified provider event.
- Gateway admin readiness display for mode, keys, endpoint, and lifecycle behavior.

### Required environment variables for Stripe

```txt
SF_PAYMENT_PROVIDER=stripe
SF_PAYMENT_MODE=test
SF_STRIPE_SECRET_KEY=sk_test_...
SF_STRIPE_WEBHOOK_SECRET=whsec_...
SF_STRIPE_PUBLISHABLE_KEY=pk_test_...
```

Use `SF_PAYMENT_MODE=live` and live keys only after test checkout and webhook events are verified.

## Publishing Workflow v1

New/updated files:

- `includes/publishing.php`
- `admin/publishing.php`
- `api/publishing-tick.php`
- `admin/release-schedule.php`
- `database/migrations/013_gateway_publishing_workflow_v1.sql`

### Capabilities

- Unified publishing registry across episodes, videos, songs, albums, and products.
- Draft / scheduled / published / archived workflow controls.
- Release date/time controls.
- Subscriber / premium / founding fan early-access controls.
- Featured content flag support when the content table supports it.
- Publishing event audit table.
- Due-run endpoint that promotes scheduled items when release time has arrived.

## SQL

Run after migration 012:

```txt
database/migrations/013_gateway_publishing_workflow_v1.sql
```

The installer has been updated to run base SQL plus migrations `001` through `013`.
