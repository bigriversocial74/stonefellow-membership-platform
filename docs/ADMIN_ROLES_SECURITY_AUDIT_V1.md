# Admin Roles / Permissions v1 + Security Hardening / Audit Log v1

This combined phase adds:

1. Phase 29: Admin Roles / Permissions v1
2. Phase 30: Security Hardening + Audit Log v1

## Added

- `includes/admin_security.php`
- `admin/roles.php`
- `admin/security-dashboard.php`
- `admin/audit-log.php`
- `api/security-audit.php`
- `database/migrations/018_admin_roles_security_audit.sql`

## Updated

- `admin/index.php`
- `includes/installer.php`

## Admin Roles / Permissions v1

Capabilities:

- admin roles table
- admin permissions table
- role-to-permission matrix
- user-to-role assignments
- default system roles
- module-level permission keys
- super admin fallback for existing admin users
- role manager page
- admin user role assignment UI
- security API coverage

Default system roles:

- Super Admin
- Content Admin
- Support Admin
- Finance Admin
- Analyst

Permission modules:

- security
- audit
- content
- members
- billing
- ops
- analytics
- settings

## Security Hardening + Audit Log v1

Capabilities:

- security dashboard
- security audit event log
- permission denial logging
- role assignment logging
- role change logging
- admin session tracking
- route/module permission map helper
- sensitive action audit helper
- admin audit JSON API

## SQL

New migration:

```txt
database/migrations/018_admin_roles_security_audit.sql
```

Creates:

- `admin_roles`
- `admin_permissions`
- `admin_role_permissions`
- `admin_user_roles`
- `security_audit_events`
- `admin_security_sessions`

Installer now runs migrations through `018`.

## Existing installs

Apply migration `018` after migration `017`.
