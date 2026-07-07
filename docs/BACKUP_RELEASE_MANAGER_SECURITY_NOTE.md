# Security Note

The backup/release manager records operational metadata and readiness checks.

It does not expose secrets or raw database dump contents in the UI. Config references should remain descriptive and should not include passwords, private keys, or raw credentials.

Access is limited through existing admin permission helpers:

- Backup manager: `admin.settings.manage`
- Release manager: `admin.ops.manage`
