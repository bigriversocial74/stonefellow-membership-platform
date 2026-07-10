# Stonefellow Data Integrity, Operations & Recovery Audit v1

## Scope

This phase reviews database migration safety, relational integrity, installer behavior, backup evidence, restore readiness, release controls, monitoring, deployment preflight, and recovery operations.

## Initial score

**5.5/10**

The platform already had useful schema, backup, release, monitoring, and incident tables. The primary weakness was that several critical states were administrative labels rather than enforced operational facts.

## Material findings

1. Schema repair deleted migration-ledger rows and could re-run every historical SQL file.
2. Modified previously applied migrations could be executed again instead of being rejected as checksum drift.
3. Schema repair had no maintenance-window flag, recent-backup gate, typed database confirmation, or advisory lock.
4. Backup records could be marked verified without an artifact digest, size, timestamp, storage reference, or passed restore tests.
5. Production restore checks and release tasks could be waived.
6. Releases could be marked ready, deploying, or deployed without a full commit SHA, verified backup, completed tasks, current migrations, or rollback instructions.
7. Deployment preflight did not treat database integrity and recovery failures as blocking launch conditions.
8. Operational health did not check strict SQL mode, foreign-key enforcement, non-InnoDB tables, orphaned records, migration checksum drift, or fresh verified backups.

## Remediation

### Migration safety

- Dynamically discovers numbered SQL migrations.
- Compares repository SHA-256 checksums with `schema_migrations`.
- Classifies migrations as current, pending, missing, checksum-mismatched, or orphaned.
- Applies only never-before-applied migrations.
- Rejects modified applied migrations and requires a new migration file.
- Protects repair with `SF_ALLOW_SCHEMA_REPAIR=1`, exact database confirmation, production maintenance mode, a verified backup from the previous 24 hours, and a MySQL advisory lock.

### Backup and restore

A verified backup now requires:

- database, uploads, and configuration components marked verified;
- a storage location or external reference;
- artifact SHA-256;
- artifact byte size;
- artifact creation time; and
- every restore-readiness check passed.

Production restore checks cannot be waived.

### Release controls

Ready, deploying, and deployed states require:

- a full 40-character Git commit SHA;
- a source branch;
- all release tasks passed;
- a linked evidence-backed verified backup;
- a production backup no older than 24 hours;
- current migration checksums; and
- production rollback instructions.

A blocked release is retained as a draft with a release event explaining the gate failure.

### Integrity and operations

The new operations audit checks:

- installer lock and sensitive configuration permissions;
- disk capacity;
- database connectivity, foreign-key enforcement, strict SQL mode, and InnoDB use;
- common orphan relationships;
- migration drift and installer coverage;
- fresh verified backup evidence;
- release-gate readiness;
- failed jobs and notifications; and
- open critical incidents.

## Added interfaces

- `admin/operations-recovery.php`
- protected `admin/migration-checker.php`
- evidence-gated `admin/backups.php`
- protected `admin/releases.php`
- expanded `admin/system-health.php`
- blocking operations checks in `deploy/preflight.php`

## Automated verification

- `tests/data_ops_recovery_smoke.php`
- `tools/data-ops-recovery-audit.php`
- GitHub Actions PHP lint, smoke, and static audit gates

## SQL

**No SQL required.**

This phase uses the existing migration ledger, backup/release tables from migration 019, and monitoring/incident tables from migration 020. Deployments missing those earlier migrations must apply them through the protected migration process.

## Deployment requirements

1. Keep `SF_ALLOW_SCHEMA_REPAIR=0` during normal operation.
2. Record a real backup artifact and digest; do not mark a checklist complete based only on intent.
3. Perform a restore rehearsal against an isolated database and media location.
4. Confirm the restored site can authenticate an administrator, query critical tables, and resolve protected media.
5. Run `deploy/preflight.php` and resolve every blocking failure before production release.

## Final static score

**10/10** after remediation, subject to CI and environment-level backup/restore rehearsal.
