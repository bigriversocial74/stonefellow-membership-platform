# Stonefellow Release Candidate & Production Launch Promotion v1

## Purpose

This phase converts a passed staging certificate into a controlled production promotion. It binds one existing deployment release, one 100% launch certification, the complete integration scenario matrix, one fresh verified backup, one exact Git commit, independent approvals, deployment artifact evidence, post-deploy verification, and rollback readiness.

## Promotion lifecycle

`draft → approved → deploying → deployed → verified`

Failure paths:

- `draft`, `approved`, `deploying`, or `deployed` may move to `failed`.
- `deploying` or `deployed` may move to `rolled_back` only after required rollback checks pass.
- `verified`, `rolled_back`, `failed`, and `superseded` promotions are immutable.

## Creation gate

A promotion can be created only when:

- the deployment release exists and has a full 40-character commit SHA;
- every existing release, migration, task, rollback, and backup gate passes;
- the selected launch certificate is passed at 100%;
- the certificate commit exactly matches the release commit;
- all required staging integration scenarios have a passed execution;
- the selected backup is fully verified, recent, and is the same backup linked to the release; and
- rollback trigger and procedure documentation are meaningful.

## Independent approvals

Technical, operations, security, and business approvals are separate records. In the default production configuration:

- every approval must use a distinct authenticated approver;
- the promotion creator cannot approve the promotion;
- approved decisions require an evidence reference and meaningful decision note; and
- rejected or missing approvals block promotion.

## Deployment evidence

The approved package requires a source reference and SHA-256 digest. Deployment automation may POST bounded JSON to:

`/api/production-deployment-event.php`

Required header:

`X-Stonefellow-Deployment-Signature: <HMAC-SHA256 of raw request body>`

Each request must contain the promotion UUID, unique event ID, and exact deployed commit. Events are privacy-redacted, hashed, idempotent, and may update only allowlisted launch checks.

## Post-deploy and rollback gates

Verification requires authentication, billing, media, notification, scheduler, preflight, and monitoring checks. Rollback requires an available decision owner, verified command/package sequence, current restore evidence, and a measured drill.

## Initial score

The existing release subsystem had strong backup, migration, task, and rollback-note controls but no certificate binding, scenario proof, independent approval, artifact digest, deployment event correlation, or post-deploy state machine: **6.4/10**.

## Final source score

The phase smoke test and ten-section static audit require **10/10**. Operational verification still requires real approvers, a real artifact, signed deployment events, production smoke checks, and monitoring evidence.

## SQL

SQL required: `database/production_launch_promotion_v1.sql`.
