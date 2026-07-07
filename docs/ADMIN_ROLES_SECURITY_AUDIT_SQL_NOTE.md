# SQL Note

This phase adds a new migration key.

New migration:

```txt
database/migrations/018_admin_roles_security_audit.sql
```

Tables:

- `admin_roles`
- `admin_permissions`
- `admin_role_permissions`
- `admin_user_roles`
- `security_audit_events`
- `admin_security_sessions`

Installer:

- `includes/installer.php` now runs migrations through `018`.

Existing installs:

- Apply migration `018` after migration `017`.
