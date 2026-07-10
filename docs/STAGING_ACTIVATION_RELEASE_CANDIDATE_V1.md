# Stonefellow Staging Activation & Release Candidate v1

Initial source score: **7.2/10**  
Final source/control score: **10/10**

This phase does not pretend that static code proves a live launch. It adds one immutable staging-activation record that binds operational evidence to an exact commit and one frozen release-candidate record that binds the approved artifact SHA-256 to that same commit.

## Ten activation sections

1. Environment and secrets
2. Real launch catalog
3. Protected media and playback
4. Membership, authentication, roles, and progress
5. Stripe Connect commerce and subscription transactions
6. Transactional delivery and scheduler health
7. Desktop, mobile, accessibility, and performance
8. Backup, restore, and rollback evidence
9. Launch certification, integration scenarios, and preflight
10. Exact commit, freeze plan, approvals, and release candidate

## Main workflow

1. Import `database/migrations/025_staging_activation_release_candidate.sql`.
2. Deploy the exact commit being activated.
3. Set `SF_ENV=staging`, explicit allowed hosts, HTTPS proxy behavior where needed, and unique staging secrets.
4. Set `SF_RELEASE_COMMIT_SHA` to the exact 40-character commit.
5. Complete a 100% catalog readiness snapshot for that commit.
6. Process the real audio and video catalog and run fresh storage health evidence.
7. Complete Stripe test merchandise and membership transactions.
8. Record manual browser, authentication, progress, delivery, refund, restore, and approval evidence.
9. Complete the existing launch certification and every integration-matrix scenario for the same commit.
10. Complete a fresh verified backup and deployment release gate.
11. Complete the activation run at 100%.
12. Create a release candidate using the artifact URL and independently calculated SHA-256.
13. Freeze the candidate with scope, owners, rollback threshold, and approval notes.
14. Run `deploy/staging-activation-preflight.php` before production promotion.

## Security and integrity controls

- Passed activation runs are immutable.
- Passed manual checks require a detailed result and a source reference.
- Evidence supports provider events, transactions, browser tests, backups, restores, approvals, URLs, and artifacts.
- Evidence and check results are SHA-256 bound.
- The status API requires a timestamped HMAC signature and exposes no credentials.
- Candidate creation requires a 100% activation, exact-commit catalog snapshot, exact-commit launch certificate, complete scenario coverage, a fresh verified backup, a passing deployment release gate, a valid artifact URL, and a 64-character SHA-256.
- Candidate freeze revalidates the gate before making the candidate immutable for promotion.

## Operational boundary

The committed source controls score 10/10. Operational certification remains evidence-driven. It is not complete until the deployed staging environment produces real browser, provider, payment, media, delivery, backup, restore, and exact-artifact evidence and the release candidate is frozen.
