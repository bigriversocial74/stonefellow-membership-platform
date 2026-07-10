# Stonefellow AI Provider Connection & Staging Certification v1

## Purpose

This phase converts the static 10/10 agentic source audit into a repeatable staging certification workflow. It does not automatically enable production autonomy.

## Installation

1. Deploy the branch to a staging environment.
2. Import `database/ai_staging_certification_v1.sql`.
3. Configure `SF_ENV=staging`, HTTPS/proxy handling, allowed hosts, and a dedicated 32+ character `SF_AI_SETTINGS_SECRET`.
4. Configure OpenAI and Anthropic credentials, selected models, budgets, token limits, and image limits in `admin/ai-settings.php`.
5. Open `admin/ai-staging-certification.php` and create a certification run.

## Certification workflow

### Automated health gate

The automated gate verifies:

- staging environment mode, HTTPS detection, allowed hosts, and AI secret readiness;
- certification, provider, audit, policy, mission, storyboard, and media queue tables;
- active provider configuration and nonzero budgets/limits;
- scoped AI administration permissions;
- retry classification for timeout, 429, conflict, and 5xx responses;
- malformed and oversized model-output rejection;
- MySQL advisory-lock contention using two independent database sessions;
- duplicate checklist protection; and
- single-winner mission item claims inside a rolled-back fixture transaction.

### Provider connection tests

Each provider test performs one tiny, non-mutating text request through the same encryption, budget, usage-reservation, throttling, locking, TLS, retry, and output-boundary controls used by production AI features. The test updates the existing provider `test_status`, `test_message`, `tested_at`, and `key_status` fields.

### Snapshot restore tests

Storyboard, scene, and episode tests:

1. write an `ai_pre_mutation_snapshot` audit record;
2. lock the selected row;
3. apply a temporary title marker;
4. restore the exact original value; and
5. commit only when the restored value matches.

Record ID `0` selects the newest available record.

### Cost reconciliation

The dashboard compares the current month's conservative reserved AI cost, request count, tokens, and images with manually entered provider invoice totals. A reconciliation passes when the variance is within the larger of $1.00 or 10 percent.

### Manual evidence

The required manual controls cover generated-media quality/moderation and backup/restoration rehearsal. Notes are retained with evidence hashes in the certification checklist.

## Completion rule

A run passes only when every required check is passed, with no failed, pending, running, or skipped required checks.

## Safety boundary

- No automatic production enablement.
- No automatic reduction of approval requirements.
- No background provider test daemon.
- No story/content mutation during provider connection tests.
- Rollback tests restore the original value inside a locked transaction.
- Mission and duplicate-submit checks use temporary transaction fixtures that are rolled back.
- Production autonomy remains approval-required until a staging run passes and an administrator explicitly changes policy later.
