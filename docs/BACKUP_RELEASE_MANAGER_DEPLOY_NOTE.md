# Deploy Note

Migration `019` should be applied after migration `018`.

After deployment:

1. Create a backup record in `admin/backups.php`.
2. Mark readiness checks.
3. Create a release record in `admin/releases.php`.
4. Link the backup record to the release.
5. Record deployment and rollback notes.
