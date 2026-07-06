# Production Deployment Package v1

This phase adds a deployment-facing package layer for moving Stonefellow from repository source to a hosting account.

## Added

- `.env.example`
- `deploy/preflight.php`
- `docs/PRODUCTION_DEPLOYMENT_PACKAGE_V1.md`

## Once-and-done install path

For a fresh host:

1. Download the repository ZIP from `main`.
2. Upload and extract into the web root.
3. Visit the site URL.
4. Open `install.php`.
5. Confirm server checks.
6. Enter database host, name, user, and password.
7. Run the installer SQL step.
8. Create the first admin account.
9. Open `admin/qa.php` and `deploy/preflight.php`.

## Preflight

Open in browser or run from CLI:

```bash
php deploy/preflight.php
```

The script returns a non-zero exit code when failed launch checks exist.

## Required production keys

Copy `.env.example` into the host's environment system and set real values. Do not commit real secrets.

Important keys:

- `SF_HASH_SALT`
- `SF_MEDIA_SIGNING_KEY`
- `SF_DB_HOST`
- `SF_DB_NAME`
- `SF_DB_USER`
- `SF_DB_PASS`
- `SF_STRIPE_SECRET_KEY` when live Stripe is enabled
- `SF_STRIPE_WEBHOOK_SECRET` when live Stripe is enabled

## Launch gate

Launch only after:

- installer SQL completes
- admin account is created
- QA score has no failed checks
- system health has no critical errors
- content audit is acceptable
- payment gateway is tested in sandbox/test mode
- email notifications are verified
- secure media full files are protected
