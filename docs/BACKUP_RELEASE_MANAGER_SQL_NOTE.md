# SQL Note

This phase adds a new migration key.

New migration:

```txt
database/migrations/019_backup_release_manager.sql
```

Tables:

- `backup_profiles`
- `backup_runs`
- `restore_readiness_checks`
- `deployment_releases`
- `deployment_release_tasks`
- `deployment_events`

Installer:

- `includes/installer.php` now runs migrations through `019`.

Existing installs:

- Apply migration `019` after migration `018`.
