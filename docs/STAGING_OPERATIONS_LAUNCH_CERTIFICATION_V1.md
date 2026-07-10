# Stonefellow Staging Operations & Launch Certification v1

## Purpose

This module converts Stonefellow's static audit gates into a persistent, evidence-backed deployed launch process. A run cannot pass until every required authentication, billing, media, notification, content, AI, operations, browser, recovery, and release check is passed.

## Workflow

1. Deploy the exact release candidate to a dedicated HTTPS staging host.
2. Import `database/staging_launch_certification_v1.sql`.
3. Configure `.env.staging.example` values with unique staging secrets and provider test credentials.
4. Create a run in `/admin/staging-launch-certification.php` with the exact 40-character Git commit SHA.
5. Run automated checks.
6. Execute each manual integration scenario and attach a source reference, provider event, artifact hash, browser result, backup record, restore record, or approval note.
7. Complete the run only when all required checks pass and the score is 100%.
8. Run `/deploy/preflight.php`; production promotion remains blocked without a matching passed certificate.

## Safety properties

- Passed runs are immutable.
- Every state change is recorded in the event ledger.
- Evidence is bounded, hashed, and associated with a specific check and run.
- Placeholder, weak, or reused secrets fail automated checks.
- Staging payment configuration must use Stripe test/sandbox mode.
- Log/sandbox email providers do not satisfy the deployed delivery gate.
- AI certification must already be 100% passed.
- Launch completion requires a 40-character target commit and every required check passed.

## Initial source score

The existing codebase had strong subsystem audits but no whole-platform deployed evidence workflow: **6.2/10**.

## Final source score

The new ten-section static audit and smoke test require **10/10** before merge. Real operational certification still depends on executing the scenarios in the deployed staging environment.

## SQL

SQL is required: `database/staging_launch_certification_v1.sql`.
