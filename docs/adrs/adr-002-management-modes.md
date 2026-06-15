# ADR-002: Management Modes

## Status
PROPOSED

## Context
WHMCS resources are managed via different interfaces with varying degrees of API coverage:
- **Local API** (best): Reliable, documented, idempotent
- **UI/Playwright** (fallback): Only for resources without API (servers, product groups)
- **Filesystem** (edge cases): Hooks and templates (version controlled, not via API)
- **SQL Emergency** (last resort): Only on backup snapshot with special approval

We need a consistent way to declare which mode applies to each resource in YAML state.

## Decision
Define 4 management modes in all YAML resources:

```yaml
management_mode: api  # One of: api, ui, filesystem, manual_seeded, sql_emergency
```

### Mode Specifications

#### 1. `api` (Default, Preferred)
- Use Local API or equivalent
- Fully idempotent
- Can be applied repeatedly without side effects
- Examples: Settings, Gateways, Products, Registrars, TLDs
- Exit on error: YES

#### 2. `ui` (No API Available)
- Use Playwright for UI automation
- Test on STAGING first
- Examples: Servers, Product Groups
- Exit on error: YES
- Requires: Headless browser, stable HTML selectors

#### 3. `filesystem` (Version Controlled)
- Tracked in Git, not in WHMCS state
- Includes: hooks/ and templates_custom/
- Applied via: `git pull` on server, then restart services
- Exit on error: YES
- Note: Not managed by whmcs-state CLI, managed separately by deployment pipeline

#### 4. `manual_seeded` (One-Time Setup)
- Created once, referenced forever (not re-applied)
- Examples: DNS records, SSL certificates (created manually, only URL stored)
- State just documents: `{"name": "example.com", "ssl_cert_id": "123", "management_mode": "manual_seeded"}`
- Exit on error: NO (reference only, creation is out-of-band)

#### 5. `sql_emergency` (Last Resort, Requires Approval)
- Only used when no API or UI exists
- Requires explicit approval in AGENTS.md
- Only on STAGING first
- Only after full backup snapshot
- Includes SQL diff in manifest for audit
- Examples: (rare) direct database state for unsupported resources
- Exit on error: YES
- Requires: DBA review, database schema locked during apply

## Consequences

### Positive
- Clear contract per resource type
- Adapters know what interface to use
- Non-API resources not blocked (can use UI)
- Emergency fallback documented (not hidden)
- Audit trail: manifest shows which mode was used

### Negative
- Requires Playwright maintenance for UI mode
- UI-based resources more fragile (selector breakage)
- SQL emergency requires ceremony (backup, approval, review)
- Developers must understand each mode's limitations

## Governance

### API Mode Resources
Priority: `api` adapters first
- SettingsAdapter ✅
- GatewayAdapter ✅
- ProductAdapter (initial: GetProducts export/diff/verify, AddProduct create-only when WHMCS IDs are resolved)
- RegistrarAdapter (initial: GetRegistrars export/diff/verify, read-only apply guard)
- TldAdapter (initial: GetTLDPricing export/diff/verify, CreateOrUpdateTLD apply)

### UI Mode Resources
Priority: `ui` adapters after all APIs
- ServerAdapter (pending)
- ProductGroupAdapter (pending)
- Playlist test suite required

### Filesystem Resources
Handled separately by deployment pipeline
- Not in whmcs-state CLI scope
- Git-based version control
- Server-side hooks/ deployment

### Manual Seeded Resources
Documentation in state files only
- No CLI management
- Reference/audit purposes

### SQL Emergency
Only with explicit ticket and DBA sign-off
- Never on PROD without STAGING drill
- Requires pre-backup snapshot verification
- Manifest includes: SQL diff, approval ticket, DBA sign-off

## Related ADRs
- ADR-001: Version target (WHMCS 8.13.x LTS)
- ADR-003: SSH/Tailscale access model
- ADR-004: Rollback & disaster recovery
- ADR-005: CI/CD pipeline phases

## Examples

### Settings (api mode)
```yaml
settings:
  management_mode: api
  CompanyName: JMGS Corp
  SystemURL: https://billing.example.com
```

### Gateways (api mode)
```yaml
gateways:
  management_mode: api
  stripe:
    enabled: true
    visible: true
    settings:
      api_key: "{{ vault.stripe.api_key }}"
```

### Servers (ui mode)
```yaml
servers:
  - management_mode: ui
    name: Web-01-USA
    module: cpanel
    hostname: web01.datacenter.com
    # Playwright will automate server creation UI
```

### Hooks (filesystem mode)
```yaml
hooks:
  - management_mode: filesystem
    name: CustomBillingLogic
    file: includes/hooks/custom_billing.php
    # Versioned in Git, deployed via CI/CD
```

### DNS Records (manual_seeded mode)
```yaml
dns_records:
  - management_mode: manual_seeded
    name: billing.example.com
    record_id: "dns_12345"
    notes: "Created in web host control panel"
```

## Migration Path
1. Sprint 3: Declare mode for Settings, Gateways (api)
2. Sprint 4: Adapt ProductAdapter, RegistrarAdapter (api)
3. Sprint 5: Implement ServerAdapter, ProductGroupAdapter (ui)
4. Sprint 6: Document filesystem resources (hooks, templates)
5. Sprint 7-8: No sql_emergency resources in initial rollout

## Related Issues
- GitHub: whmcs-cac#? (track as implementation)
