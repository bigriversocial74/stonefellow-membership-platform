# Stonefellow Content Publishing, Search, Import & Moderation Audit v1

## Initial score: 4.8/10

The initial review found that scheduled publishing did not perform state changes, publishing updates were not transaction-locked, the admin publishing page mutated data during GET requests, content imports defaulted records to published/active, file and row limits were absent, imports could partially commit, search fell back to static content after an empty database result, SQL wildcard characters were not escaped, indexed results were not checked against source availability, comments stored raw IP and browser data, missing comment tables returned false success, and cookie-authenticated API writes lacked CSRF/origin enforcement.

## Remediation

### Publishing

- Validates content type, status, access level, release dates, and publication windows.
- Uses row locks and database transactions for state changes.
- Records before/after publishing events.
- Reindexes searchable content after publishing changes.
- Runs due publishing under a database advisory lock.
- Promotes legacy scheduled rows, archives expired windows, and records a due-run event.
- Removes all write behavior from publishing page GET requests.
- Adds a POST-only secret-authenticated publishing cron endpoint.

### Imports

- Defaults content to draft or inactive and file records to non-primary.
- Enforces file size, extension, MIME, JSON depth, row, and column limits.
- Rejects duplicate or malformed CSV headers and inconsistent row widths.
- Validates enum values, access levels, numeric ranges, slugs, and path traversal.
- Generates a preview digest.
- Requires explicit `IMPORT` confirmation in the admin interface.
- Commits every import batch in one transaction under an advisory lock.
- Rolls back the entire transaction on any row failure.
- Performs import rollback in a locked transaction.

### Search

- Bounds and normalizes search queries.
- Escapes SQL `LIKE` wildcards.
- Applies query rate limits.
- Uses static fallback only when the database index is unavailable, not when a query simply has no results.
- Filters results by member access level.
- Rechecks every indexed result against the source content status and publication window.
- Rejects unsafe or off-site result URLs.

### Comments, reactions, and moderation

- Requires an active user and a valid content target.
- Validates length, link count, repeated-character spam, duplicate comments, and reply-thread ownership.
- Applies member and IP rate limits.
- Stores HMAC hashes instead of raw IP/browser identifiers.
- Fails closed when comment or reaction tables are unavailable.
- Requires approved targets for reactions and maintains comment reaction counts transactionally.
- Restricts moderation queues and status changes to authorized administrators.
- Records moderation notes and events in a transaction.
- Removes public exposure of member email addresses and moderation counts.
- Requires same-origin, CSRF-protected, POST-only API writes.

## Final static score: 10/10

CI requires all ten content-integrity sections to score 10/10.

## Verification

- `tests/content_integrity_smoke.php`
- `tools/content-integrity-audit.php`
- Full repository PHP syntax validation and all prior audit gates

## SQL

**No SQL required.** Existing migration 011 import tables and migration 013 publishing, search, comment, reaction, and moderation tables are used.

## Deployment boundary

Before production launch, configure a separate 32+ character `SF_PUBLISHING_RUN_SECRET`, schedule the POST cron endpoint, test imports against a staging database snapshot, verify search visibility with accounts at every access tier, and conduct a live moderation/abuse rehearsal.
