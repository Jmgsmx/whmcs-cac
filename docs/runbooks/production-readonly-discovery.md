# Production Read-Only Discovery

Use this mode when connecting to a real WHMCS instance that manages live sites.

## Safety Defaults

Set both read-only guards before using real credentials:

```bash
set WHMCS_CAC_READ_ONLY=1
set WHMCS_UI_READ_ONLY=1
```

These guards block:

- CLI `apply`
- Playwright `liveApply`

They still allow read-only discovery:

- `validate`
- `doctor`
- `export-live`
- `diff`
- `auth:save`
- `capture:selectors`
- `analyze:selectors`

## Do Not Commit Secrets

Do not paste WHMCS credentials into chat, docs, tests, or Git.

Use local environment variables or an ignored `.env` file. The repo already ignores `.env` and `.env.*`.

## Read-Only CLI Discovery

```bash
set WHMCS_CAC_READ_ONLY=1
php cli/bin/whmcs-state validate --env=state/envs/dev
php cli/bin/whmcs-state export-live --whmcs-root=C:\path\to\whmcs --resource=all
```

Do not run `apply` against a real production instance unless read-only mode is disabled intentionally after a backup and staging drill.

## Read-Only UI Discovery

```bash
cd playwright
set WHMCS_UI_READ_ONLY=1
set WHMCS_ADMIN_URL=https://billing.example.com/admin
npm run auth:save
npm run capture:selectors
npm run analyze:selectors
```

Selector capture reads page structure only. It does not submit forms.

## Production Apply Requirements

Before any real apply:

- Staging clone exists.
- Full backup verified.
- `diff` reviewed.
- `apply --dry-run` reviewed.
- Rollback drill completed.
- `WHMCS_CAC_READ_ONLY` and `WHMCS_UI_READ_ONLY` are unset deliberately.
