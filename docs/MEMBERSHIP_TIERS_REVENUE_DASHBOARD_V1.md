# Membership Tiers / Access Packaging v2 + Launch Revenue Dashboard v1

This combined phase adds:

1. Phase 23: Membership Tiers / Access Packaging v2
2. Phase 24: Launch Revenue Dashboard v1

## Added

- `includes/membership_tiers.php`
- `includes/revenue_dashboard.php`
- `admin/tier-manager.php`
- `admin/revenue-dashboard.php`
- `api/tier-packages.php`
- `api/revenue-summary.php`
- `database/migrations/015_membership_tiers_revenue_dashboard.sql`

## Updated

- `subscribe.php`
- `account-billing.php`
- `admin/index.php`
- `includes/installer.php`

## Membership Tiers / Access Packaging v2

Capabilities:

- public tier packaging helper
- fallback tier packages for no-database/static mode
- editable admin tier manager
- access labels
- launch positions: entry, core, premium, founder
- public pricing visibility
- benefit matrix tables
- plan/benefit mapping
- tier packages JSON API
- upgraded public pricing page
- account billing upgrade path cards

## Launch Revenue Dashboard v1

Capabilities:

- MRR
- ARR
- 30-day subscription revenue
- 30-day merch revenue
- total 30-day revenue
- active subscriptions
- paid member count
- checkout starts
- checkout completions
- checkout conversion rate
- churn/grace risk
- comments/reactions/feed saves as engagement conversion signals
- plan revenue breakdown
- recent payment transactions
- launch revenue snapshots
- revenue summary JSON API

## SQL

New migration:

```txt
database/migrations/015_membership_tiers_revenue_dashboard.sql
```

Creates:

- `membership_tier_benefits`
- `membership_tier_benefit_map`
- `launch_revenue_snapshots`
- `checkout_conversion_events`

Also extends `subscription_plans` with packaging fields and confirms plan access columns:

- `allows_video_streaming`
- `allows_playlists`
- `access_label`
- `benefit_matrix_json`
- `upgrade_path_json`
- `launch_position`
- `is_public`

Installer now runs migrations through `015`.

## Existing installs

Apply migration `015` after migration `014`.
