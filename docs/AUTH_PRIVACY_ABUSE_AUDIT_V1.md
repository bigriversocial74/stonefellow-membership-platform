# Stonefellow Authentication, Authorization, Privacy & Abuse Audit v1

## Scope

This phase reviews password policy, sign-in abuse, session lifecycle, remember tokens, account recovery, public registration, administrator authorization, privileged-session safety, user privacy rights, telemetry privacy, and browser security policy.

## Initial score: 5.4/10

Material findings included:

- public account and reset flows still accepted eight-character passwords;
- login attempt records contained raw email, IP address, and browser identifiers;
- sign-in throttling was not enforced in the public login path;
- remember-me behavior was enabled without a production opt-in boundary;
- reset tokens could be displayed by the public recovery page;
- password resets did not revoke all account tokens and tracked administrator sessions;
- public first-user registration could promote the account to administrator;
- secure logout did not revoke the current administrator session or rotate the session ID;
- revoked administrator sessions could be reactivated by session tracking;
- unmapped administrator routes did not fail closed to a permission;
- legacy administrators could inherit every permission without an explicit owner rule;
- the final super-administrator role assignment could be removed;
- no self-service account export or controlled account deactivation existed; and
- browser responses lacked an application Content Security Policy.

## Remediation

### Authentication and abuse prevention

- Enforces a configurable minimum password length with a hard floor of 12 characters.
- Rejects common passwords and passwords containing the account email name.
- Enforces database-backed or session-backed login throttling.
- Rehashes passwords after successful login when the current PHP algorithm requires it.
- Hashes recent login-attempt email, IP, and browser identifiers after authentication processing.
- Limits account registration and password-recovery attempts.

### Session and token lifecycle

- Adds idle and absolute session expiration.
- Adds a browser fingerprint check and secure session-ID rotation.
- Disables remember-me in production unless `SF_ALLOW_REMEMBER_ME=1`.
- Clears and revokes disallowed remember cookies.
- Revokes account tokens and tracked administrator sessions after a successful password reset.
- Revokes the current tracked administrator session during logout.

### Registration and recovery containment

- Blocks public first-user administrator creation in production unless explicitly enabled.
- Requires protected installer owner setup by default.
- Uses generic account-creation and password-recovery responses to reduce account enumeration.
- Displays development reset links only outside production and only when `SF_SHOW_DEVELOPMENT_RESET_LINK=1`.

### Administrator authorization

- Requires administrator authentication and a route permission on every administrator surface.
- Adds exact and category-based permission mapping.
- Unknown administrator routes fail closed to the settings permission.
- Revoked or expired tracked administrator sessions cannot be reactivated.
- Legacy full-permission fallback is restricted to the oldest active administrator account.
- System roles cannot be deactivated or have their keys changed.
- The final super-administrator assignment cannot be removed.

### Privacy rights

- Adds `account-privacy.php`.
- Provides a signed-in user JSON data export scoped to the account user ID.
- Provides controlled account deactivation only after active memberships and unresolved merchandise orders are cleared.
- Deactivation disables sign-in and revokes tokens/sessions while preserving financial, tax, fraud-prevention, security, and legal records where required.

### Browser policy

- Adds a Content Security Policy with self-only defaults, blocked plugins, restricted forms, restricted framing, controlled YouTube frames, controlled fonts, and production HTTP upgrade protection.
- Adds Cross-Origin-Resource-Policy and Origin-Agent-Cluster headers.
- Supports report-only CSP rollout through `SF_CSP_REPORT_ONLY=1`.

## Automated verification

- `tests/auth_privacy_abuse_smoke.php`
- `tools/auth-privacy-abuse-audit.php`
- Full PHP syntax validation
- All existing security, AI, revenue, recovery, and front-end gates

## Final static score: 10/10

All ten reviewed sections must score 10/10 for CI to pass.

## SQL

**No SQL required.** Existing authentication token, login attempt, administrator role, administrator session, audit, membership, order, library, and progress tables are used.

## Deployment boundary

Before production launch, verify the CSP against the deployed media and payment integrations, run password-reset email delivery tests, conduct multi-browser session expiration tests, test administrator role separation with real accounts, and complete a formal privacy/retention policy review for the operating jurisdiction.
