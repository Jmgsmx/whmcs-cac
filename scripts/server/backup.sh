#!/usr/bin/env bash
set -euo pipefail

ENV_NAME="${1:-dev}"
TS="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_ROOT="/opt/whmcs-backups/${ENV_NAME}/${TS}"
WHMCS_ROOT="/var/www/whmcs"
DB_NAME="${WHMCS_DB_NAME:-}"
DB_USER="${WHMCS_DB_USER:-}"
DB_PASS="${WHMCS_DB_PASS:-}"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "Missing DB env vars: WHMCS_DB_NAME, WHMCS_DB_USER, WHMCS_DB_PASS" >&2
  exit 1
fi

mkdir -p "$BACKUP_ROOT"

mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_ROOT/db.sql.gz"
cp "$WHMCS_ROOT/configuration.php" "$BACKUP_ROOT/configuration.php" || true
rsync -a "$WHMCS_ROOT/includes/hooks/" "$BACKUP_ROOT/hooks/" || true
rsync -a "$WHMCS_ROOT/templates/" "$BACKUP_ROOT/templates/" || true

sha256sum "$BACKUP_ROOT"/* > "$BACKUP_ROOT/SHA256SUMS.txt" || true

echo "$BACKUP_ROOT"
