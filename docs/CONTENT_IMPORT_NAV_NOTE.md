# Content Import Navigation Note

Content Import + Seed Manager v1 adds these direct admin routes:

- `admin/import.php`
- `admin/seed-manager.php`
- `admin/demo-content.php`

The pages cross-link to each other and use the existing admin shell/security layer. A later UI polish PR can add permanent sidebar entries in `includes/admin_catalog.php` if desired.
