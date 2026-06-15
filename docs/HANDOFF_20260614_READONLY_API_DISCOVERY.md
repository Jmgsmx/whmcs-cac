# Handoff 2026-06-14: Read-Only API Discovery

## Current State

Repository: `D:\whmcs-cac`

Branch: `development`

Remote: `origin/development`

Latest committed work:

- `cc57446 feat: add production read-only guards`
- `f42fbda feat: add ui selector manifest guard`
- `13bf4da test: add selector artifact analyzer`
- `a00ffe6 chore: add whmcs admin storage-state helper`
- `578a567 test: add whmcs ui selector capture`

The repo is configured to support safe read-only discovery before touching any live WHMCS-managed sites.

## Safety Guards

Use these whenever working with real production credentials:

```powershell
$env:WHMCS_CAC_READ_ONLY="1"
$env:WHMCS_UI_READ_ONLY="1"
```

These block:

- CLI `apply`
- Playwright `liveApply`

They still allow:

- `validate`
- read-only API probes
- UI auth storage capture
- selector capture/analyze
- offline tests

## Important Correction

The API token created today appears to be a **WHM/cPanel API token**, not a **WHMCS API Authentication Credential**.

They are different systems:

### WHMCS API

Used by WHMCS billing/admin automation.

Expected `.env` shape:

```env
WHMCS_CAC_READ_ONLY=1
WHMCS_UI_READ_ONLY=1

WHMCS_API_URL=https://billing.example.com/includes/api.php
WHMCS_API_IDENTIFIER=...
WHMCS_API_SECRET=...
WHMCS_ADMIN_URL=https://billing.example.com/admin
```

Authentication: POST fields `identifier` and `secret`.

### WHM/cPanel API

Used by WHM server/cPanel automation.

Expected `.env` shape:

```env
WHMCS_CAC_READ_ONLY=1
WHMCS_UI_READ_ONLY=1

WHM_API_URL=https://server.example.com:2087
WHM_API_USER=root
WHM_API_TOKEN=...
```

Authentication: HTTP header `Authorization: whm username:token`.

Do not paste either token into chat, docs, commits, tests, or logs.

## What Happened Today

The read-only WHMCS probes returned:

```text
Invalid IP 189.175.147.232
```

After reviewing the cPanel/WHM documentation, the more important finding is that the token flow being configured was for WHM/cPanel, not WHMCS. Tomorrow, get the real WHMCS API credential pair from WHMCS Admin instead.

## Tomorrow's Next Step

Create or retrieve **WHMCS API Authentication Credentials** in WHMCS Admin.

Needed values:

- API URL: `https://<billing-domain>/includes/api.php`
- API Identifier
- API Secret
- Admin URL: `https://<billing-domain>/<admin-path>`

Then update local ignored `.env` only:

```env
WHMCS_CAC_READ_ONLY=1
WHMCS_UI_READ_ONLY=1

WHMCS_API_URL=https://<billing-domain>/includes/api.php
WHMCS_API_IDENTIFIER=<identifier>
WHMCS_API_SECRET=<secret>
WHMCS_ADMIN_URL=https://<billing-domain>/<admin-path>
```

Run only read-only checks first:

```powershell
cd D:\whmcs-cac
$env:WHMCS_CAC_READ_ONLY="1"
$env:WHMCS_UI_READ_ONLY="1"
```

Then test read-only API actions:

- `GetProducts`
- `GetTLDPricing`
- `GetRegistrars`

Do not run `apply` against the real instance.

## Current Validation

Last verified before this handoff:

- `composer test`: OK, `30 tests, 76 assertions`
- `npm test`: OK, `12 passed, 3 skipped`
- `validate`: OK
- Git clean and synced with `origin/development`

## Relevant Runbooks

- `docs/runbooks/production-readonly-discovery.md`
- `docs/runbooks/ui-selector-capture.md`

## Open Work

- Add an `ExternalApiClient` for WHMCS API credentials after the real WHMCS API pair is available.
- Keep WHM/cPanel API support separate if needed later.
- Capture WHMCS Admin selectors after `WHMCS_ADMIN_URL` and login are available.
- Do not enable live apply until staging, backup, dry-run, and rollback drill are complete.
