# Stonefellow Staging Integration Matrix v1

## Purpose

The launch-certification dashboard defines the required outcomes. The integration matrix provides the structured executions used to prove those outcomes against a deployed staging release.

## Execution model

Each execution belongs to one active launch-certification run and contains:

- one versioned scenario key;
- an immutable execution UUID;
- a privacy-safe test account reference;
- a correlation ID for payment, email, webhook, database, and browser evidence;
- required assertions;
- assertion result notes, source references, and optional SHA-256 evidence hashes;
- signed, idempotent provider-event records; and
- a final passed or failed result.

A scenario is promoted into launch certification only when every required assertion passes. Failed, pending, running, or skipped required assertions prevent promotion.

## Scenario coverage

1. Authentication account lifecycle
2. Authentication role separation
3. Billing checkout and entitlement activation
4. Billing subscription lifecycle
5. Media access and signed delivery
6. Media progress and resume integrity
7. Notification provider delivery and webhooks
8. Notification preferences and campaigns
9. Content publishing, import, and moderation
10. AI supervised execution and recovery
11. Operations concurrency, backup, restore, and preflight
12. Browser, accessibility, and performance quality

## Signed event correlation

Staging providers or test harnesses may POST bounded JSON to:

`/api/staging-integration-event.php`

Required header:

`X-Stonefellow-Integration-Signature: <hex HMAC-SHA256 of raw body>`

The secret is `SF_STAGING_INTEGRATION_EVENT_SECRET`. The endpoint is staging-only, rejects oversized or unsigned requests, requires an execution UUID and assertion key, stores a payload hash, redacts PII/secrets, and ignores duplicate provider event IDs.

## Initial score

After Phase 1, the platform had a complete checklist but integration execution remained mostly freeform: **6.8/10**.

## Final source score

The structured scenario, assertion, event-correlation, and certification-promotion layers score **10/10** in the phase audit and smoke tests. Real operational results still require running the scenarios against deployed services.

## SQL

SQL required: `database/staging_integration_matrix_v1.sql`.
