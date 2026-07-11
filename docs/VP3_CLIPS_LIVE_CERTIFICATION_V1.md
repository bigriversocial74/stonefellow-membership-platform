# Stonefellow VP3 Clips Live Integration Certification v1

The certification wizard proves the installed creator platform can safely render and publish through the current central bridge credential before live feed creation is enabled.

## Checks

- Migrations 027 and 028
- Stonefellow license receipt and product identity
- Encrypted bridge settings
- cURL with verified TLS
- OpenSSL
- FFmpeg and FFprobe
- Writable private clip output storage
- Public HTTPS base URL
- Signed central context request
- Two-second synthetic 9:16 render and poster probe

The synthetic files are deleted immediately. No protected master or certification media is sent to VP3.

## Approval flow

A passing signed report enters `passed` status centrally. A VP3 administrator reviews and approves it. Stonefellow refreshes the status and changes to `live` publishing mode. Approval is bound to the exact bridge UUID and expires after 180 days; rotating the credential requires a new certification.

## SQL

Import `database/migrations/028_vp3_clips_live_certification.sql` after migration 027.
