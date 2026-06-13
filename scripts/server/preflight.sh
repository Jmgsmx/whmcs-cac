#!/usr/bin/env bash
set -euo pipefail

echo "== OS =="
if [ -f /etc/os-release ]; then
  cat /etc/os-release
else
  lsb_release -a || true
fi

echo "== PHP =="
php -v || true
php -m | sort || true

echo "== MySQL Client =="
mysql --version || true

echo "== Disk =="
df -h || true

echo "== Cron =="
if command -v systemctl >/dev/null 2>&1; then
  systemctl status cron --no-pager || true
else
  service cron status || true
fi

echo "== WHMCS Root =="
ls -la /var/www/whmcs || true
