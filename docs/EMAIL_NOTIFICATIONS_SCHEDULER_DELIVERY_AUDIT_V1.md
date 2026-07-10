# Stonefellow Email, Notifications, Scheduler & Delivery Audit v1

## Initial score: 4.9/10

The initial review found unlocked notification dispatch, no message idempotency, unlimited immediate retries, no exponential backoff, production log-provider false positives, header-injection exposure in `mail()` fields, active markup in administrator email previews, overlapping scheduled jobs, continuously due manual jobs, duplicate lifecycle tasks, campaign recipients marked sent without proving channel outcomes, stored-but-unenforced preferences, unsigned scheduler operations, weak campaign API write controls, and unsigned provider webhooks storing full payloads.

## Remediation

### Templates and provider boundary

- Removes CR/LF and control characters from email headers.
- Validates recipient/from addresses.
- Removes scripts, forms, embedded objects, event handlers, and script URLs from email HTML.
- Requires every template placeholder to be declared.
- Escapes variables before HTML rendering.
- Sandboxes administrator previews.
- Makes log/sandbox and PHP `mail()` providers fail closed in production unless separately enabled.

### Queue delivery

- Adds deterministic or explicit idempotency keys.
- Reuses existing queue rows rather than creating duplicate sends.
- Adds global queue and per-message MySQL advisory locks.
- Locks queue rows before attempts.
- Adds configurable maximum attempts and exponential retry backoff.
- Distinguishes queued, sent, failed, skipped, and canceled states.
- Prevents retrying successful/skipped records.
- Reports real provider outcomes in the administrator console.

### Preferences and campaigns

- Enforces email and in-app preferences for nontransactional messages.
- Preserves essential authentication, billing, commerce, security, and administrator notifications.
- Forces broad marketing audiences to honor preferences.
- Stores per-channel email-log and in-app-message identifiers.
- Marks a recipient sent only when every attempted channel succeeds.
- Marks fully suppressed recipients skipped.
- Reuses channel records during retries.
- Makes sent/archived campaigns immutable.

### Scheduler

- Excludes manual-frequency jobs from automatic due selection.
- Calculates the next future run deterministically.
- Adds global due-run and per-job advisory locks.
- Records started/success/failed run leases and counts.
- Prevents duplicate retention/support tasks.
- Dispatches both notification queue rows and due campaigns.
- Requires explicit administrator confirmation for manual runs.
- Adds a POST-only, secret-authenticated scheduler cron endpoint.

### Webhooks and APIs

- Requires CSRF for administrator JSON writes.
- Requires administrator permissions for job/campaign endpoints.
- Requires POST methods for mutations.
- Requires HMAC signatures and provider event IDs for delivery webhooks.
- Processes webhook events transactionally and idempotently.
- Redacts addresses, tokens, headers, and message bodies from webhook evidence.
- Prevents duplicate events from overwriting prior state.

## Final static score: 10/10

All ten reviewed delivery sections must score 10/10 for CI to pass.

## Verification

- `tests/delivery_integrity_smoke.php`
- `tools/delivery-integrity-audit.php`
- Full PHP syntax validation and every previous security, AI, revenue, recovery, front-end, authentication, and content-integrity gate

## SQL

**No SQL required.** Existing migrations 006 and 017 provide the template, queue, preference, webhook, scheduler, run, campaign, recipient, thread, and member-message tables.

## Deployment boundary

Before production launch, configure a real transactional email provider or explicitly approved PHP-mail integration, configure separate 32+ character notification webhook and scheduler secrets, test bounce/delivery signatures with the provider, run concurrent worker tests against MySQL, validate email rendering in major clients, and execute preference/unsubscribe and campaign-retry rehearsals with staging accounts.
