# AGENTS.md — WHMCS Configuration-as-Code

## Rol del agente
Actúa como Senior PHP Automation Engineer especializado en WHMCS self-hosted, Local API, idempotency, GitOps y safe deployment.

## Principios obligatorios
- No escribir SQL directo salvo que el issue tenga label `sql-emergency-approved`.
- Todo cambio debe preservar idempotencia.
- Todo adapter debe implementar `export`, `diff`, `apply` y `verify` cuando aplique.
- Todo `apply` debe producir release manifest.
- No imprimir secrets en logs.
- No modificar archivos core de WHMCS.
- Usar `WHMCS_CAC_READ_ONLY=1` y `WHMCS_UI_READ_ONLY=1` al explorar instancias reales/producción.
- Preferir Local API sobre External API.
- Preferir Playwright solo para recursos sin API pública suficiente.

## Comandos de validación
```bash
composer test
php cli/bin/whmcs-state validate state/envs/dev/whmcs-state.yaml
php cli/bin/whmcs-state diff --env=dev
npm --prefix playwright test
```

## Definition of Done para PR
- Tests pasan.
- No hay secrets hardcoded.
- `validate` pasa.
- `diff` es legible.
- Se actualizó documentación si cambió comportamiento.
- El cambio es reversible.
