# Merch Cart + Order Runtime v1

## What this phase adds

This phase turns the merch section from static demo pages into an operational ecommerce runtime that can work in two modes:

1. **Session preview mode** when no database is configured.
2. **Database mode** when `SF_DB_HOST`, `SF_DB_NAME`, `SF_DB_USER`, and `SF_DB_PASS` are set and the SQL files are installed.

## New and upgraded runtime files

- `includes/store.php` — shared merch/catalog/cart/order helper layer.
- `merch.php` — live product listing from database or static fallback.
- `product.php` — live product detail page with variants, inventory, access gates, and add-to-cart form.
- `cart.php` — add/update/remove/clear cart flow.
- `checkout.php` — sandbox checkout that validates customer/shipping details and creates an order.
- `order-confirmation.php` — receipt-style order confirmation page.
- `api/cart.php` — JSON cart runtime endpoint for later AJAX/mobile UI. Mutating requests require `csrf_token` or `X-CSRF-Token`.

## New admin files

- `admin/products.php` — merch product manager with pricing, inventory, status, access level, image asset, featured/drop flags, and variants.
- `admin/orders.php` — order queue, order detail, fulfillment/status updates, and order status history.

## New migration

- `database/migrations/005_merch_order_runtime.sql`

Adds optional order columns and the runtime audit tables:

- `orders.receipt_token`
- `orders.payment_status`
- `orders.fulfillment_status`
- `orders.customer_phone`
- `orders.shipping_method`
- `orders.notes`
- `order_status_history`
- `product_inventory_movements`

## Runtime behavior

### Cart

- Guest carts are tied to the server session.
- Signed-in carts are tied to `users.id` when database mode is active.
- Cart rows support products and variants.
- Subscriber-only merch checks the existing membership access layer before cart entry.

### Checkout

- Validates email, full name, and shipping address when physical goods are present.
- Calculates subtotal, shipping, estimated tax, and total.
- Uses sandbox payment mode by default.
- Creates a paid order record in database mode.
- Converts the active cart to `converted`.
- Records line items in `order_items`.
- Reduces inventory from product or variant stock.
- Records merch payment in `payment_transactions` when migration 004 is installed.
- Records status history and inventory movements when migration 005 is installed.

### Admin order management

- Admins can review recent merch orders.
- Admins can update status to pending, paid, fulfilled, canceled, or refunded.
- Status changes are written to `order_status_history`.

## Production processor path

This is intentionally still sandbox checkout. The next production payment phase should replace sandbox submission with Stripe/processor hosted checkout or Elements while keeping the same order creation and payment transaction interfaces.
