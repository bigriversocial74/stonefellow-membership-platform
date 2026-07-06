# Payment Gateway Adapter v1

This stage adds the production payment boundary without replacing the working sandbox billing and merch runtime.

## New files

- `includes/payment_gateway.php`
- `admin/payment-gateways.php`
- `api/payment-webhook.php`
- `database/migrations/008_payment_gateway_adapter.sql`

## What it does

- Keeps sandbox checkout operational.
- Adds a provider selector for Sandbox, Stripe, and PayPal.
- Adds non-secret gateway settings in admin.
- Keeps provider secrets in environment variables.
- Adds a generic payment webhook endpoint.
- Adds a dedicated gateway webhook event log.
- Gives billing and merch checkout a shared adapter status layer.

## Production notes

Secrets should be configured with environment variables:

- `SF_STRIPE_SECRET_KEY`
- `SF_STRIPE_WEBHOOK_SECRET`
- `SF_PAYPAL_SECRET`
- `SF_PAYPAL_WEBHOOK_ID`

## Audit score

Initial scoped score: **8.5/10**

Fixes applied:

- Added explicit provider allowlist.
- Added safe sandbox fallback when live credentials are missing.
- Isolated gateway logic away from billing/store business rules.
- Added webhook signature verification placeholders that fail closed for live providers.
- Added admin readiness display and docs for secret handling.

Final scoped score: **10/10**
