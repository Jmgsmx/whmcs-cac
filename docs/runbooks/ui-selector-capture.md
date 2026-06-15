# UI Selector Capture Runbook

Use this runbook before enabling live Playwright apply for UI-managed WHMCS resources.

## Purpose

Capture sanitized DOM structure for WHMCS Admin pages that do not have reliable public APIs:

- Servers and server groups: `configservers.php`
- Product groups: `configproducts.php`

The capture stores labels, form field names, button text and links. It does not store input values.

## Prerequisites

- A staging WHMCS Admin URL.
- A logged-in browser storage state if the admin page requires authentication.

## Commands

```bash
cd playwright
npm install
```

Create a local authenticated storage state:

```bash
set WHMCS_ADMIN_URL=https://billing-staging.example.com/admin
npm run auth:save
```

The browser opens visibly. Complete WHMCS admin login, then return to the terminal and press Enter. The default storage state path is:

```text
playwright/storage-state/staging-admin.json
```

Without a saved session, the capture may record the login page or redirect:

```bash
set WHMCS_ADMIN_URL=https://billing-staging.example.com/admin
npm run capture:selectors
```

With a saved session:

```bash
set WHMCS_ADMIN_URL=https://billing-staging.example.com/admin
set WHMCS_STORAGE_STATE=storage-state/staging-admin.json
npm run capture:selectors
npm run analyze:selectors
```

## Output

Selector snapshots are written to:

```text
playwright/selector-artifacts/
```

These files are ignored by Git because they describe a live admin instance.

## Next Step

Review the captured JSON and promote stable selectors into `playwright/lib/ui-adapters.ts` before enabling any live UI apply flow.

Run `npm run analyze:selectors` first. It fails if the snapshots look like login pages or if the expected admin path was not captured.
