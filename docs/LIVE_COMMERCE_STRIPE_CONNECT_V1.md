# Stonefellow Live Commerce & Stripe Connect v1

## Scope

This phase converts merchandise checkout from a local sandbox-only path into a provider-agnostic commerce runtime with Stripe as the first active provider. It covers merchant onboarding, server-side order pricing, discounts, shipping, tax configuration, inventory reservations, Stripe Checkout, connected-account settlement, signed webhooks, refunds, disputes, receipts, customer history, reconciliation, and maintenance.

## Initial static score: 5.8/10

The existing store already had useful carts, server catalog prices, transactional sandbox inventory decrement, protected receipt keys, subscription Stripe verification, and order history. Material production gaps included:

- no merchant payment-account model;
- no Stripe Connect onboarding;
- merchandise checkout intentionally disabled in production;
- no pending provider checkout or inventory reservation lifecycle;
- no connected-account destination charge;
- no webhook routing distinction between subscriptions and merchandise;
- no provider-confirmed merchandise refunds or disputes;
- no reconciliation dashboard;
- no customer order-history page or downloadable receipt; and
- hard-coded shipping and tax behavior.

## Remediation

### Provider architecture

- Adds a provider registry with Stripe implemented first.
- PayPal and Square remain visible but fail closed until complete signed adapters exist.
- Adds a merchant profile and provider-account records without storing provider secrets in the database.
- Test and live connected accounts are separated.

### Stripe Connect onboarding

- Creates Stripe connected accounts with an Express dashboard configuration.
- Requests card-payment and transfer capabilities.
- Uses authenticated, single-use Stripe-hosted Account Links.
- Collects currently and eventually due requirements.
- Synchronizes `details_submitted`, `charges_enabled`, `payouts_enabled`, and requirement evidence.
- Processes `account.updated` webhooks.

### Checkout and inventory

- Reprices every cart from live catalog and variant records.
- Applies server-validated discounts.
- Uses configurable shipping, free-shipping threshold, and tax basis points.
- Creates pending orders before provider checkout.
- Reserves inventory under database locks for 35 minutes.
- Prevents overlapping active checkout sessions for one cart.
- Uses Stripe Checkout destination charges to route merchandise funds to the connected merchant account.
- Routes recurring membership subscriptions to the same connected account with destination-charge subscription data.
- Records recurring `invoice.paid` and `invoice.payment_failed` lifecycle evidence.
- Supports an optional platform application fee.
- Consumes inventory only after a signed settled-payment webhook.

### Payment lifecycle

- Routes merchandise and subscription events separately.
- Verifies Stripe signatures, amount, currency, payment status, checkout identity, and duplicate payment use.
- Handles asynchronous payment success/failure and session expiration.
- Records paid merchandise transactions with idempotency keys.
- Requests full refunds through Stripe and waits for webhook confirmation before restocking.
- Records partial refunds without unsafe automatic item restocking.
- Flags disputes and records dispute closure outcomes.

### Customer and operations

- Adds customer order history.
- Adds downloadable HTML receipts.
- Adds merchant provider onboarding controls.
- Adds payment reconciliation for failed webhooks, stale checkouts, missing transactions, amount mismatches, and expired reservations.
- Adds a signed maintenance endpoint for reservation cleanup.

## Final static score: 10/10

All ten audited sections must score 10/10 for CI to pass.

## SQL

**SQL required:**

`database/migrations/022_live_commerce_stripe_connect.sql`

## Environment

Required Stripe platform configuration:

- `SF_PAYMENT_PROVIDER=stripe`
- `SF_PAYMENT_MODE=test` or `live`
- `SF_STRIPE_PUBLISHABLE_KEY`
- `SF_STRIPE_SECRET_KEY`
- `SF_STRIPE_WEBHOOK_SECRET`
- `SF_STRIPE_PLATFORM_FEE_BPS`
- `SF_COMMERCE_MAINTENANCE_SECRET`

Commerce calculation settings:

- `SF_COMMERCE_SHIPPING_FLAT_CENTS`
- `SF_COMMERCE_FREE_SHIPPING_THRESHOLD_CENTS`
- `SF_COMMERCE_TAX_RATE_BPS`

## Operational boundary

The 10/10 score is a source-code and static-control score. Operational certification still requires:

1. creating a real Stripe Connect test account through onboarding;
2. confirming charges and payouts become enabled;
3. configuring connected-account webhooks;
4. completing Stripe test Checkout payments;
5. testing card failures, expiration, recurring invoice renewal/failure, refund, and dispute simulations;
6. verifying connected-account settlement and any platform fee;
7. running concurrent checkout and inventory tests against MySQL; and
8. reconciling Stripe Dashboard evidence against Stonefellow orders and transactions.
