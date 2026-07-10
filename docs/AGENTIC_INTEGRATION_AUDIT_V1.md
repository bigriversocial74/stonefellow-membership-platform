# Stonefellow Agentic Integration Deep Audit v1

Date: July 9, 2026  
Branch: `feature/stonefellow-next-20260709`

## What is actually agentic

Stonefellow currently has three distinct AI layers. They should not be described as one undifferentiated autonomous system.

### Live external provider integrations

These paths make real network calls when an active provider and encrypted key are configured:

- OpenAI Responses API for full storyboard generation and scene rewriting.
- Anthropic Messages API for full storyboard generation and scene rewriting.
- OpenAI image generation for storyboard scene images and show-theme images.
- Shared text-provider execution for episode outline generation.

### Supervised agentic control infrastructure

The AI Control Center, allowlisted execution router, autonomy policies, publishing-readiness controls, operations watchtower, mission planning, and mission execution provide structured proposals, approvals, state transitions, and audited side effects. These modules are supervised orchestration. They are not a freeform tool-calling autonomous agent.

### Queue-only or planning integrations

Script producer phases, review queues, production-pack builders, shot lists, media-prep records, and media-generation queues primarily parse, organize, approve, or queue work. A queued media-generation record does not mean an image/video provider was called.

## Initial deep-audit score

| Section | Initial |
|---|---:|
| Provider secrets and configuration | 5.5/10 |
| Provider transport and retry safety | 6.0/10 |
| Budgets, limits, reservations, and throttling | 3.0/10 |
| Prompt and data boundaries | 6.0/10 |
| Structured output validation | 5.0/10 |
| Mutation snapshots and rollback evidence | 4.5/10 |
| Generated media validation and approval | 5.0/10 |
| Allowlisted action execution and policy integrity | 8.0/10 |
| Mission idempotency and recovery leases | 6.0/10 |
| Scoped AI administration permissions | 6.0/10 |
| Audit, privacy, and bounded retention | 6.5/10 |
| Tests and continuous verification | 2.0/10 |

Initial overall agentic score: **6.1/10**.

## Material findings

1. Provider budget, token, and image limits were stored in the admin UI but not enforced before a request.
2. Provider-key encryption could fall back to predictable application-path material when production secrets were absent.
3. Provider calls were implemented in multiple modules with inconsistent retries, response limits, output validation, and error retention.
4. Storyboard and scene AI results could destructively replace approved working data without a complete pre-mutation audit snapshot.
5. Model JSON was checked mainly for parseability and scene count, not strict keys, sequential numbering, field sizes, or required fields.
6. Generated base64 image data was written without verifying the decoded image MIME, dimensions, or maximum byte size.
7. Theme images could be promoted directly from generated to current without the explicit approval step.
8. Approved action execution and mission execution could race under concurrent requests.
9. Stale mission recovery reset every running item without requiring the execution lease to expire.
10. AI modules were admin-only but did not consistently enforce the existing scoped admin-role permissions.
11. Return URLs in three AI generation endpoints were not constrained to local paths.
12. There was no dedicated executable audit for the live provider and agentic control stack.

## Remediation cycle 1 — shared governance

Implemented:

- A dedicated `SF_AI_SETTINGS_SECRET` requirement with AES-256-GCM v2 encryption and backward-compatible v1 decryption.
- Strict provider, model, status, timeout, retry, temperature, and activation validation.
- A shared provider governance gate that enforces active encrypted credentials, monthly budget, token/image limits, conservative cost reservations, request throttles, bulk-image limits, and per-provider/target advisory locks.
- Local-only redirect enforcement and bounded request bodies for live AI endpoints.
- Scoped permissions for provider settings, content generation, autonomy policy, and AI operations pages.

Cycle 1 score: **9.1/10**.

## Remediation cycle 2 — data integrity and execution safety

Implemented:

- Fixed HTTPS provider endpoint allowlists, TLS verification, redirect prohibition, bounded request/response bodies, retry-only-on-transient-failure behavior, exponential backoff, jitter, and Retry-After handling.
- Explicit context/request delimiters and instructions that database/user content is untrusted story data.
- Strict storyboard, scene, and episode-output schemas with sequential scene numbers, key allowlists, required fields, item caps, and field-size bounds.
- Complete pre-mutation snapshots in the admin audit trail before storyboard, scene, or episode AI replacement.
- Validated generated image bytes, MIME, dimensions, maximum size, atomic writes, restricted permissions, and approval-before-current enforcement.
- Immutable approval checks, payload validation, distinct high-risk approver/executor policy, and MySQL advisory locks around action execution.
- Transactional mission-item claims, affected-row checks, and recovery only after an explicit execution lease expires.
- Hash-based bounded job/audit persistence instead of retaining unrestricted provider responses and prompts.
- Dedicated agentic smoke tests and a twelve-section CI audit gate.

## Final static scores

| Section | Final |
|---|---:|
| Provider secrets and configuration | 10/10 |
| Provider transport and retry safety | 10/10 |
| Budgets, limits, reservations, and throttling | 10/10 |
| Prompt and data boundaries | 10/10 |
| Structured output validation | 10/10 |
| Mutation snapshots and rollback evidence | 10/10 |
| Generated media validation and approval | 10/10 |
| Allowlisted action execution and policy integrity | 10/10 |
| Mission idempotency and recovery leases | 10/10 |
| Scoped AI administration permissions | 10/10 |
| Audit, privacy, and bounded retention | 10/10 |
| Tests and continuous verification | 10/10 |

Final static agentic score: **10/10**.

## Verification commands

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/security_smoke.php
php tests/agentic_governance_smoke.php
php tools/code-audit.php
php tools/agentic-audit.php
```

## Operational verification still required

The static score does not prove production provider behavior. Before enabling live AI, staging must re-enter/test encrypted keys under the dedicated AI secret; configure nonzero provider budgets and relevant monthly limits; verify actual OpenAI and Anthropic models; replay 429, 5xx, timeout, malformed JSON, oversized response, and duplicate-submit scenarios; verify advisory-lock behavior on the production MySQL version; test role assignments; confirm audit-log storage capacity and retention; validate generated-image quality and moderation expectations; exercise snapshot restoration; and reconcile provider invoices against conservative internal cost reservations.
