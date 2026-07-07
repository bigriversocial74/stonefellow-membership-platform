# Backup + Release Manager Review Notes

Branch: `feature/backup-release-manager-v1`

Post-merge checks:

1. Apply migration `019` after migration `018`.
2. Open `admin/backups.php` as an admin with settings permission.
3. Create a backup record.
4. Update database/uploads/config statuses.
5. Mark restore readiness checks passed/failed/waived.
6. Open `api/backup-readiness.php` as admin.
7. Open `admin/releases.php` as an admin with ops permission.
8. Create a release record linked to a backup record.
9. Mark release checklist tasks passed/failed/waived.
10. Add a release event.
11. Open `api/release-manager.php` as admin.
12. Confirm `install.php` lists migration `019`.

SQL:

- New migration required: `database/migrations/019_backup_release_manager.sql`.
- Installer now runs through migration `019`.
