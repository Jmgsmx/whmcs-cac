# Rollback Runbook

This runbook describes the steps to rollback a release.

1. Identify target `env` and `release_id` to restore.
2. Pause WHMCS cron (run as www-data): `sudo systemctl stop cron` or stop cron user tasks.
3. Verify backup snapshot integrity (checksums).
4. Restore DB: `gunzip -c /opt/whmcs-backups/<env>/<ts>/db.sql.gz | mysql -u<user> -p<pass> <db>`
5. Restore `configuration.php` and hooks/templates from backup.
6. Run `php cli/bin/whmcs-state doctor --env=<env>` and fix issues.
7. Run smoke tests (Playwright) against staging.
8. Resume cron.
9. Document incident and notify stakeholders.
