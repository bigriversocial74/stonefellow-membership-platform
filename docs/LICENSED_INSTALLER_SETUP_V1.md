# Stonefellow Licensed Installer & Setup v1

## Purpose

This phase separates the public VP3 installer landing page from the technical setup wizard and places a product-license gate before any server, database, filesystem, or migration diagnostics are displayed.

## Public installer

`/install.php` remains the branded product introduction. It does not execute or render PHP extension checks, writable-directory checks, database status, SQL migration status, credentials, server paths, or install-lock instructions.

Legacy `install.php?step=...` URLs redirect into the protected setup wizard.

## Secure setup wizard

`/setup/index.php` uses the following ordered flow:

1. Product License
2. Server Requirements
3. Database Connection
4. Build Platform
5. Owner Account
6. Completion

The setup route sends `noindex`, `nofollow`, `noarchive`, and no-store cache headers. Direct access to later steps is rejected until the prerequisite state is complete.

## Product identity

Default product ID:

```text
VP3-STONEFELLOW-001
```

Product configuration is stored in `config/product-license.php`.

## Offline ledger provider

The first provider is `offline_ledger`. Create `config/license-ledger.php` from `config/license-ledger.example.php` and add one record per manually issued license.

Only the normalized license-key SHA-256 is stored in the ledger. The complete key is shown once when issued and is never written to the installation receipt.

Vendor command:

```bash
php vendor-tools/license-ledger-entry.php \
  --customer="Customer Name" \
  --email="customer@example.com" \
  --domain="example.com" \
  --license-id="LIC-000001"
```

The utility prints the key and a copy-ready ledger entry. `vendor-tools/` must be excluded from customer release packages.

## License validation

The installer validates:

- Key SHA-256
- Product ID
- Active or development status
- Expiration date
- Authorized domain, including wildcard domains
- Edition and update eligibility metadata

The adapter functions are:

```php
sf_license_validate();
sf_license_activate_setup();
sf_license_status();
sf_license_revalidate_receipt();
```

Set `SF_LICENSE_PROVIDER=remote_api` later to connect the same setup UI to the future VP3 licensing service through `sf_license_remote_provider_validate()`.

## Activation receipt

After successful installation, Stonefellow writes:

```text
storage/private/license-receipt.json
```

The receipt contains the product ID, license ID, installation UUID, customer, edition, authorized domains, activated domain, timestamps, update eligibility, provider, key fingerprint, and ledger-record fingerprint.

It never contains the complete license key or its ledger hash.

## Administrator page

`/admin/license.php` displays the protected license identity, activation receipt, authorized domains, expiration, updates-through date, provider, and fingerprints. Administrators can revalidate the receipt against the local ledger.

## Release exclusions

`.releaseignore` excludes:

- Real license ledger
- Private activation receipts
- Vendor license tools
- Tests and internal tooling
- Environment files and local configuration

## SQL

No SQL required. Licensing must work before a database is connected, so the ledger and receipt are file-based.

## Verification

Permanent verification includes:

- Valid, invalid, and wrong-domain key tests
- Receipt redaction and revalidation
- Public landing isolation checks
- Setup indexing and sequencing checks
- Output-buffer hardening removal
- Ten-section source audit
- Cumulative GitHub Actions gates

Initial source readiness score: **6.8/10**

Final source/control score: **10/10**

Operational validation still requires creating a real vendor ledger entry, packaging a customer release without vendor tools or real ledger data, and completing a fresh installation on a disposable staging domain.
