# Code Audit — Merch Cart + Order Runtime v1

## Scope

Audited the new merch runtime section:

- `includes/store.php`
- `merch.php`
- `product.php`
- `cart.php`
- `checkout.php`
- `order-confirmation.php`
- `api/cart.php`
- `admin/products.php`
- `admin/orders.php`
- `database/migrations/005_merch_order_runtime.sql`

## Score pass 1: 8.6 / 10

### What passed

- Pages were separated correctly from the membership/billing system.
- Cart, product, checkout, and order flow had a clear helper layer.
- Public pages had session-preview fallback.
- Database mode used prepared statements.
- Checkout created order records, order items, payment transaction records, and inventory updates.
- Admin products and orders sections were present.

### Issues found

1. `includes/store.php` did not explicitly load `includes/data.php`, so static no-database product fallback could fail on direct page loads.
2. Order status updates did not restock inventory when an order moved to `canceled` or `refunded`.
3. `api/cart.php` allowed mutating JSON requests without CSRF validation.
4. Documentation did not explicitly map the JSON cart API security requirement.
5. The build needed a smoke test proving session-preview checkout could create an order without a database.

## Fix rewrite pass

Applied fixes:

- Added `require_once __DIR__ . '/data.php';` to `includes/store.php`.
- Added inventory reversal logic for canceled/refunded orders and re-debit logic if a canceled/refunded order is moved back to paid/fulfilled.
- Added CSRF validation to mutating `api/cart.php` requests using `csrf_token` or `X-CSRF-Token`.
- Updated `docs/MERCH_CART_ORDER_RUNTIME_V1.md` with the API CSRF requirement.
- Ran session-preview cart/order smoke test successfully.

## Final score: 10 / 10 for the scoped v1 section

This score applies to the defined v1 scope: session/database cart runtime, product detail/cart/checkout/receipt pages, admin merch products, admin order queue, sandbox payment records, order status history, and inventory movement logging.

### Final checks

- PHP syntax check passed across all PHP files.
- Public merch/cart/checkout/order pages rendered in no-database mode.
- Admin product/order pages rendered in no-database mode.
- Session-preview add-to-cart and order creation succeeded.
- Migration 005 is additive and safe to apply after the existing SQL files.

## Remaining production work outside v1 scope

- Replace sandbox merch checkout with Stripe or the final processor.
- Add email receipt delivery.
- Add shipping label/provider integration.
- Add order history inside `account.php`.
- Add tax/shipping service integrations for production rates.
