# Production Backup / Restore Manager v1 + Deployment Release Manager v1

This combined phase adds:

1. Phase 31: Production Backup / Restore Manager v1
2. Phase 32: Deployment Release Manager v1

## Added

- `includes/backup_release.php`
- `admin/backups.php`
- `admin/releases.php`
- `api/backup-readiness.php`
- `api/release-manager.php`
- `database/migrations/019_backup_release_manager.sql`

## Updated

- `admin/index.php`
- `includes/installer.php`

## Production Backup / Restore Manager v1

Capabilities:

- backup profile records
- backup run records
- backup manifest snapshot
- schema migration snapshot
- database table row-count manifest
- media/storage path coverage list
- restore readiness checklist
- backup verification status
- backup summary cards
- backup readiness JSON API

## Deployment Release Manager v1

Capabilities:

- release version records
- release status workflow
- deployment environment field
- linked backup run
- migration range tracking
- deployment checklist tasks
- preflight link
- release event history
- rollback notes
- deployment notes
- release manager JSON API

## SQL

New migration:

```txt
database/migrations/019_backup_release_manager.sql
```

Creates:

- `backup_profiles`
- `backup_runs`
- `restore_readiness_checks`
- `deployment_releases`
- `deployment_release_tasks`
- `deployment_events`

Installer now runs migrations through `019`.

## Existing installs

Apply migration `019` after migration `018`.
