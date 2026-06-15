# whmcs-cac

WHMCS Configuration-as-Code: Declarative WHMCS automation from workstation via SSH/Tailscale.

## Quick Start

### Install dependencies
```bash
cd cli
composer install
cd ../playwright
npm install
```

### Validate state
```bash
php cli/bin/whmcs-state validate --env=state/envs/dev
```

### Run tests
```bash
cd cli
composer test
cd ../
```

### Run Playwright tests
```bash
cd playwright
npm test
```

Set `WHMCS_ADMIN_URL` to enable live admin smoke tests. Without it, live UI tests are skipped and adapter contract tests still run.

### Capture WHMCS admin selectors
```bash
cd playwright
npm run auth:save
npm run capture:selectors
```

See [UI selector capture runbook](docs/runbooks/ui-selector-capture.md).

## Project Structure
- `state/envs/{dev,staging,prod}/` — YAML state declarations
- `cli/` — PHP CLI tool (validate, export-live, diff, apply, doctor)
- `playwright/` — UI automation (UI-only resources: servers, product groups)
- `scripts/` — Server and workstation bootstrap scripts
- `docs/` — ADRs, runbooks, blueprints
- `.github/workflows/` — CI/CD pipeline

## Documentation
- [Blueprint](docs/blueprint/WHMCS_Configuration_As_Code_Workstation_Blueprint.md)
- [AGENTS.md](AGENTS.md) — AI coding rules
- [Runbooks](docs/runbooks/)

## Development
- Branch: `development` for feature work
- PR to `main` for releases
- See [AGENTS.md](AGENTS.md) for coding principles
