# Sprint 1-3 Progress (Foundation → Discovery → CLI Core)

## ✅ Completed (This Session)

### Sprint 1: Foundation
- ✅ Repository structure created (github.com/Jmgsmx/whmcs-cac)
- ✅ AGENTS.md with Codex governance rules
- ✅ CI workflow template (.github/workflows/ci.yml)
- ✅ SSH/Tailscale validation scripts (scripts/server/preflight.sh)
- ✅ Development branch established

### Sprint 2: Discovery
- ✅ API coverage documentation (implicit in state files)
- ✅ export-live command skeleton (awaiting WHMCS LocalAPI connection)

### Sprint 3: CLI Core (🟡 In Progress)
- ✅ validate command (ValidateCommand.php, tests)
- ✅ Adapter pattern (AdapterInterface, ApplyResult, VerificationResult)
- ✅ SettingsAdapter (exportLive, diff, apply, verify)
- ✅ GatewayAdapter (exportLive, diff, apply, verify)
- ✅ Unit tests (SettingsAdapterTest.php with PHPUnit)
- ✅ composer.json with test runner
- ✅ phpunit.xml and test bootstrap
- ✅ README.md with Quick Start guide
- 🟡 export-live command (skeleton ready, needs WHMCS connection)
- 🟡 diff command (design ready, implementation pending)
- 🟡 apply command (design ready, implementation pending)

## 🔶 In Progress (Next Actions)

### Immediate (This Sprint)
1. **Export-Live Command** (cli/src/Command/ExportLiveCommand.php)
   - Connect to WHMCS via LocalApiClient
   - Use SettingsAdapter.exportLive()
   - Use GatewayAdapter.exportLive()
   - Return JSON snapshot with timestamp

2. **Diff Command** (cli/src/Command/DiffCommand.php)
   - Load YAML state files
   - Call export-live to get live state
   - Compare using adapters
   - Output readable diff (before applying)

3. **Additional Adapters** (parallel work)
   - RegistrarAdapter for domains/registrars
   - ProductAdapter for hosting packages
   - DomainAdapter for TLD pricing

## 📋 Pending (Sprint 4-8)

### Sprint 4: API Adapters
- [ ] Complete RegistrarAdapter (AddRegistrar, UpdateTld)
- [ ] Complete ProductAdapter (CreateProduct, UpdateProduct)
- [ ] Integration tests for each adapter

### Sprint 5: Playwright Adapters
- [ ] ServerAdapter (create servers via UI, no reliable API)
- [ ] ProductGroupAdapter (create/manage product groups)
- [ ] Playwright test suite

### Sprint 6: Backup & Rollback
- [ ] Rollback command
- [ ] Doctor command (health checks)
- [ ] Backup/restore integration tests

### Sprint 7: STAGING Deployment
- [ ] Full state apply on STAGING
- [ ] Smoke tests
- [ ] Rollback drill

### Sprint 8: PROD Cutover
- [ ] Controlled cutover with freeze window
- [ ] Release manifests
- [ ] Incident response plan

## 🔗 Files Created/Modified

### Created
- cli/src/Adapter/AdapterInterface.php
- cli/src/Adapter/Result.php (ApplyResult, VerificationResult classes)
- cli/src/Adapter/SettingsAdapter.php
- cli/src/Adapter/GatewayAdapter.php
- cli/tests/Adapter/SettingsAdapterTest.php
- cli/tests/bootstrap.php
- phpunit.xml

### Modified
- cli/bin/whmcs-state (added export-live, --resource flag)
- cli/composer.json (added PHPUnit, test script)
- .github/workflows/ci.yml (proper gates, no || true)
- README.md (Quick Start, project structure, docs links)

## 🎯 Key Metrics

- **Unit Test Coverage**: SettingsAdapter ✓, GatewayAdapter (pending), ValidateCommand ✓
- **CLI Commands Working**: validate ✓, export-live (skeleton), diff (pending), apply (pending)
- **Adapters Implemented**: Settings ✓, Gateway ✓, Registrar (pending), Product (pending)
- **Documentation**: Blueprint ✓, AGENTS.md ✓, README ✓, ADR-001 ✓, Runbook-rollback ✓

## 💡 Architecture Notes

**Adapter Pattern**: Each resource type (Settings, Gateway, Registrar, etc.) has an adapter implementing:
- `exportLive()` — fetch current state from WHMCS
- `diff(desired, live)` — calculate changes
- `apply(plan)` — execute changes idempotently
- `verify(desired)` — confirm state matches

**Result Classes**: ApplyResult (success, message, data, errors) and VerificationResult (valid, mismatches)

**Error Handling**: Adapters return empty arrays on error; Results provide error details for logging/reporting.

## 🚀 Next Commit

```bash
git add -A
git commit -m "docs: add Sprint 1-3 progress roadmap"
git push
```

Then proceed to implement export-live and diff commands on development branch.
