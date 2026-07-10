# Stonefellow Revenue, Membership & Media Access Audit v1

## Scope

Subscription checkout, payment webhooks, subscription lifecycle, entitlement calculation, signed audio/video delivery, playback tracking, libraries, playlists, player state, merch checkout, inventory, and order-receipt privacy.

## Initial static score: 5.9/10

Material findings included sandbox fallbacks, payment activation without exact amount/currency verification, stale subscription grants after plan changes, local cancellation without provider confirmation, public full-track paths, replayable user-bound media tokens, client-trusted watch/listen metrics, insufficient library/playlist input validation, paid merch orders without a live provider, inventory race exposure, and receipt lookup without a required key.

## Remediation

- Stripe checkout is idempotent and fail-closed.
- Signed webhooks must verify settled status, exact amount, exact currency, and payment identifier.
- Checkout activation locks and conditionally claims one pending checkout and rejects duplicate payments.
- Provider evidence is bounded and redacted.
- The internal billing webhook is production-disabled unless explicitly enabled and HMAC-signed.
- Provider cancellation must succeed before local subscription state changes.
- Old subscription grants expire before replacement-plan grants are created.
- Direct grants must meet the requested access level; plan capability flags control media and playlist features.
- Production demo access elevation is disabled.
- Public song pages receive previews unless the current user is entitled to full audio.
- Audio/video delivery uses signed URLs, requires a dedicated production signing key, and binds user tokens to the current account.
- Downloads require authenticated offline entitlement.
- Playback duration and completion are recomputed and bounded server-side.
- Library saves use server catalog records; playlist ownership, access, and limits are enforced.
- Non-production merch checkout re-prices and locks inventory. Production live merch checkout remains fail-closed until a verified provider adapter is implemented.
- Order confirmations require the authenticated owner or exact receipt key.

## Verification

CI runs PHP lint, existing security and agentic tests, revenue/membership/media smoke tests, and a ten-section static audit.

## Final static score: 10/10

This is a source-code score. Operational verification still requires Stripe test-mode checkout/webhooks/cancellation, signed files stored outside the public web root, concurrent MySQL inventory tests, and browser playback QA.

## Deployment

No SQL required.

Production configuration:

- `SF_PAYMENT_PROVIDER=stripe`
- `SF_STRIPE_SECRET_KEY`
- `SF_STRIPE_WEBHOOK_SECRET`
- `SF_MEDIA_SIGNING_KEY` with at least 32 random characters
- `SF_ALLOW_SANDBOX_SUBSCRIPTIONS=0`
- `SF_ALLOW_SANDBOX_MERCH=0`
- `SF_ALLOW_INTERNAL_BILLING_WEBHOOK=0`

PayPal and live merch provider processing remain disabled until full cryptographic lifecycle adapters are implemented.
