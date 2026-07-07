# Admin Roles + Audit Review Notes

Branch: `feature/admin-roles-security-audit-v1`

Post-merge checks:

1. Apply migration `018` after migration `017`.
2. Open `admin/security-dashboard.php` as admin.
3. Open `admin/roles.php` and confirm default system roles are visible.
4. Edit or create a role and save permissions.
5. Assign a role to an admin user.
6. Open `admin/audit-log.php` and confirm role change events are logged.
7. Open `api/security-audit.php` as admin.
8. Confirm `install.php` lists migration `018`.
9. Confirm existing admin users are assigned Super Admin by the migration.

SQL:

- New migration required: `database/migrations/018_admin_roles_security_audit.sql`.
- Installer now runs through migration `018`.
