# Stonefellow Full-Platform Code Audit v1

Date: July 9, 2026  
Branch: `feature/stonefellow-next-20260709`

## Scoring standard

A source section earns 10/10 only when its required controls are present, fail closed, and are enforced by the executable repository audit. This is a source-code score. Production database migrations, credentials, DNS/TLS, storage permissions, mail delivery, provider accounts, backups, taxes, shipping, and real checkout reconciliation still require staging and deployment verification.

## Baseline

| Section | Initial |
|---|---:|
| Runtime and configuration | 6.5/10 |
| Database safety | 7.0/10 |
| Authentication and sessions | 6.0/10 |
| Admin authorization | 5.5/10 |
| API and webhook integrity | 5.5/10 |
| Billing and commerce | 7.0/10 |
| Media and uploads | 8.0/10 |
| Installer and deployment | 7.5/10 |
| QA and observability | 7.0/10 |
| Maintainability and documentation | 7.5/10 |

Baseline overall: **6.8/10**.

## Remediation cycle 1

Implemented a dependency-free security bootstrap, hardened cookie sessions, periodic session rotation, request IDs, host validation, security headers, bounded request bodies, safe redirects, and strict HTTP method helpers. PDO now uses native prepares and disables MySQL multi-statements. Runtime guards add login throttling, stronger production password boundaries, remember-token rotation, first-owner protection, fail-closed admin access, and universal admin POST CSRF validation.

Cycle 1 score: **9.2/10**.

Remaining gaps were payment/webhook integrity, production sandbox boundaries, upload policy centralization, installer POST protection, and repeatable CI scoring.

## Remediation cycle 2

Implemented:

- POST-only, bounded payment and billing webhooks with explicit error responses, signatures, duplicate-event handling, and provider fail-closed behavior.
- Production prohibition on local sandbox membership activation.
- Explicit PayPal rejection until API-based webhook verification is configured.
- Centralized upload MIME, extension, size, randomized-name, storage-permission, and registry controls.
- Installer CSRF, install-lock enforcement, strict sessions, and a 12-character owner-password boundary.
- Production-safe environment documentation.
- Security smoke tests and GitHub Actions that lint every PHP file and reject any audit section below 10/10.

## Final source scores

| Section | Final |
|---|---:|
| Runtime and configuration | 10/10 |
| Database safety | 10/10 |
| Authentication and sessions | 10/10 |
| Admin authorization | 10/10 |
| API and webhook integrity | 10/10 |
| Billing and commerce | 10/10 |
| Media and uploads | 10/10 |
| Installer and deployment | 10/10 |
| QA and observability | 10/10 |
| Maintainability and documentation | 10/10 |

Final static source score: **10/10**.

## Verification

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/security_smoke.php
php tools/code-audit.php
```

## Deployment verification still required

Run migrations and the QA dashboard on staging; configure long random app, hash, media, billing, and provider webhook secrets; set real allowed hosts; verify HTTPS/HSTS behind the proxy; replay signed Stripe events to confirm idempotency; test mail and storage permissions; validate range streaming, backups and restoration; and reconcile real checkout, tax, shipping, refunds, and chargebacks. Keep PayPal disabled until its API verification and checkout adapter are complete.
