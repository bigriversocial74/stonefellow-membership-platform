# Test Plan

1. Apply migration `019` after migration `018`.
2. Open `admin/backups.php`.
3. Create a backup record.
4. Update backup statuses.
5. Mark readiness checks passed/failed/waived.
6. Open `api/backup-readiness.php`.
7. Open `admin/releases.php`.
8. Create a release record linked to a backup record.
9. Update release checklist tasks.
10. Add a release event.
11. Open `api/release-manager.php`.
12. Confirm installer lists migration `019`.
