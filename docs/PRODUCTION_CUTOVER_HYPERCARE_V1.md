# Stonefellow Production Cutover & Hypercare v1

Initial source readiness score: **7.4/10**  
Final source/control score: **10/10**

## Purpose

This phase turns the frozen exact-commit release candidate into a controlled production launch. One immutable cutover run binds the candidate, promotion, deployment release, backup, artifact URL, artifact SHA-256, deployed commit, evidence, traffic decisions, rollback thresholds, hypercare checkpoints, and final production verification certificate.

## Command center

`/admin/production-cutover.php` manages the maintenance window, automated gates, manual verification evidence, go/hold/rollback/abort decisions, staged traffic activation, incident timeline, hypercare checkpoints, production baselines, known issues, operational handoff, and final certificate.

## Launch sequence

1. Import migration 026 and deploy the exact release commit.
2. Configure the production cutover secrets and rollback thresholds.
3. Create a cutover from the frozen candidate and matching approved production promotion.
4. Run automated checks and record pre-deployment evidence.
5. Record the go decision and seed hypercare checkpoints.
6. Activate traffic through 1%, 5%, 10%, 25%, 50%, and 100% stages.
7. Complete live auth, commerce, media, delivery, browser, monitoring, and rollback checks.
8. Complete the 15-minute, 1-hour, 6-hour, 24-hour, and 72-hour checkpoints.
9. Record known issues and operational ownership.
10. Issue the production verification certificate and run the cutover preflight.

## Rollback

Thresholds cover application error rate, payment failure rate, protected-media failure rate, p95 latency, queue depth, and unresolved critical incidents. Threshold breaches generate a fail-closed rollback recommendation and immutable event evidence. A human authority records the final rollback or hold decision.

## Signed automation

- `POST /api/production-cutover-event.php` accepts HMAC-signed deployment and verification events.
- `GET /api/production-cutover-status.php` returns a timestamp-signed status snapshot.
- `/deploy/production-cutover-preflight.php` fails unless the exact deployed commit has a completed 100% cutover and production verification certificate.

## Operational boundary

The committed source controls score 10/10. Operational certification is not claimed until real production evidence exists for deployment, migrations, traffic, authentication, payments, media, notifications, browsers, monitoring, rollback readiness, all five hypercare checkpoints, known issues, ownership handoff, and the exact-commit production certificate.
