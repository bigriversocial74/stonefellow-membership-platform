# Admin Navigation + Final Production Install QA Pass v1

This phase hardens the installed platform after the major streaming features were added.

## Goals

- Make sure every new admin page is reachable from the permanent admin sidebar.
- Register every current public, member, admin, API, secure media, and deployment route in the QA route matrix.
- Bring the migration checker up to the current SQL plan through migration `013`.
- Add a post-install launch checklist page.
- Confirm fresh ZIP installs have one clear path after installation.

## Added

- `admin/launch-checklist.php`
- `docs/ADMIN_NAV_INSTALL_QA_PASS_V1.md`
- `docs/CODE_AUDIT_ADMIN_NAV_INSTALL_QA_PASS_V1.md`

## Updated

- `includes/admin_catalog.php`
- `includes/qa.php`

## Permanent admin sidebar coverage

The sidebar now includes the major post-install areas:

- Launch Checklist
- Production QA
- Migration Checker
- Routes Checker
- Security Check
- Content Audit
- System Health
- Streaming Analytics
- Media Dashboard
- Publishing
- Secure Media
- Search Discovery
- Entitlements
- Payment Gateways
- Content Import
- Seed Manager
- Demo Content
- Assets
- Settings

## QA registry coverage

The QA registry now covers:

- public/member/commerce routes
- admin routes from the same sidebar registry
- API JSON routes
- secure stream/download utility routes
- deployment preflight route
- migrations base through `013`
- tables added by content import, player state, publishing, library, and search discovery

## Launch checklist

`admin/launch-checklist.php` gives the operator one place to review:

- QA score
- failed checks
- review/manual checks
- installer path
- route matrix
- migration checker
- secure media check
- payment gateway check
- preflight script

## SQL

No new SQL migration is required.
