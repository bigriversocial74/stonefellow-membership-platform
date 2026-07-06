# Membership Tiers + Revenue Dashboard Review Notes

Branch: `feature/membership-tiers-revenue-v1`

Post-merge checks:

1. Apply migration `015` after migration `014`.
2. Open `subscribe.php` and confirm tier cards and benefit matrix render.
3. Open `account-billing.php` as a member and confirm upgrade path cards render.
4. Open `admin/tier-manager.php` and save/edit a tier.
5. Open `api/tier-packages.php`.
6. Open `admin/revenue-dashboard.php`.
7. Save a launch revenue snapshot.
8. Open `api/revenue-summary.php` as admin.
9. Confirm `install.php` lists migration `015` on a clean install.

SQL:

- New migration required: `database/migrations/015_membership_tiers_revenue_dashboard.sql`.
- Installer now runs through migration `015`.
