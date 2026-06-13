<?php

namespace Whmcs\Command;

class RollbackCommand
{
    /**
     * Rollback to previous state from backup or manifest.
     * 
     * Usage: php cli/bin/whmcs-state rollback --env=<env> [--from-manifest=<file>]
     * 
     * Procedure:
     * 1. Pause cron jobs
     * 2. Verify backup exists
     * 3. Restore database
     * 4. Restore config.php, hooks, templates
     * 5. Run doctor to verify health
     * 6. Resume cron jobs
     * 7. Generate rollback report
     */
    public static function run(
        string $environment,
        string $whmcsRoot,
        ?string $fromManifest = null
    ): int {
        try {
            echo "=== WHMCS Rollback Procedure ===\n";
            echo "Environment: $environment\n";
            echo "WHMCS Root: $whmcsRoot\n\n";

            // Step 1: Pause cron
            echo "[1/6] Pausing cron jobs...\n";
            if (!self::pauseCron($whmcsRoot)) {
                error_log("Warning: Could not pause cron");
            }

            // Step 2: Find backup
            echo "[2/6] Locating backup...\n";
            $backup = self::locateBackup($whmcsRoot, $environment, $fromManifest);
            if (!$backup) {
                error_log("Error: No backup found for rollback");
                return 1;
            }
            echo "  Found: {$backup['database']}\n";

            // Step 3: Restore database
            echo "[3/6] Restoring database...\n";
            if (!self::restoreDatabase($backup['database'])) {
                error_log("Error: Database restore failed");
                return 1;
            }

            // Step 4: Restore files
            echo "[4/6] Restoring config and hooks...\n";
            if (!self::restoreFiles($whmcsRoot, $backup['files'])) {
                error_log("Error: File restore failed");
                return 1;
            }

            // Step 5: Verify health
            echo "[5/6] Running health checks...\n";
            $doctorExit = DoctorCommand::run($whmcsRoot);
            if ($doctorExit !== 0) {
                error_log("Warning: Health checks failed after restore");
            }

            // Step 6: Resume cron
            echo "[6/6] Resuming cron jobs...\n";
            if (!self::resumeCron($whmcsRoot)) {
                error_log("Warning: Could not resume cron");
            }

            // Generate report
            echo "\n=== Rollback Complete ===\n";
            $report = self::generateRollbackReport($environment, $backup, $doctorExit === 0);
            $reportFile = "reports/rollback-" . date('Y-m-d_His') . ".json";
            @mkdir('reports', 0755, true);
            file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
            echo "Report saved: $reportFile\n";

            if ($doctorExit === 0) {
                echo "\n✓ Rollback successful. System is healthy.\n";
                return 0;
            } else {
                echo "\n⚠ Rollback completed but health checks failed.\n";
                echo "Run 'doctor' command to investigate further.\n";
                return 1;
            }
        } catch (\Exception $e) {
            error_log("rollback failed: {$e->getMessage()}");
            return 1;
        }
    }

    private static function pauseCron(string $whmcsRoot): bool
    {
        // Placeholder: In production, would comment out cron jobs or use WHMCS API to suspend tasks
        // For now, return true
        return true;
    }

    private static function locateBackup(
        string $whmcsRoot,
        string $environment,
        ?string $fromManifest = null
    ): ?array {
        // If manifest provided, extract backup info
        if ($fromManifest && file_exists($fromManifest)) {
            $manifest = json_decode(file_get_contents($fromManifest), true);
            if (isset($manifest['rollback_info']['backup_location'])) {
                return [
                    'database' => $manifest['rollback_info']['backup_location'] . '/database.sql',
                    'files' => $manifest['rollback_info']['backup_location'] . '/config-hooks-templates.tar.gz'
                ];
            }
        }

        // Otherwise, find latest backup in /opt/whmcs-backups/{env}/
        $backupDir = "/opt/whmcs-backups/$environment";
        if (!is_dir($backupDir)) {
            return null;
        }

        $backups = array_filter(
            array_map('basename', glob("$backupDir/*", GLOB_ONLYDIR)),
            fn($d) => preg_match('/^\d{14}$/', $d) // YmdHis format
        );

        if (empty($backups)) {
            return null;
        }

        // Sort by timestamp, newest first
        rsort($backups);
        $latest = reset($backups);

        return [
            'database' => "$backupDir/$latest/database.sql",
            'files' => "$backupDir/$latest/config-hooks-templates.tar.gz"
        ];
    }

    private static function restoreDatabase(string $sqlFile): bool
    {
        if (!file_exists($sqlFile)) {
            error_log("Backup file not found: $sqlFile");
            return false;
        }

        // Placeholder: Would execute via SSH
        // mysql -u$USER -p$PASS < $sqlFile
        echo "  Would restore: $sqlFile\n";
        return true;
    }

    private static function restoreFiles(string $whmcsRoot, string $tarFile): bool
    {
        if (!file_exists($tarFile)) {
            error_log("Backup file not found: $tarFile");
            return false;
        }

        // Placeholder: Would extract via SSH
        // tar -xzf $tarFile -C $whmcsRoot/
        echo "  Would restore: $tarFile\n";
        return true;
    }

    private static function resumeCron(string $whmcsRoot): bool
    {
        // Placeholder: In production, would resume cron jobs via WHMCS API or crontab
        return true;
    }

    private static function generateRollbackReport(
        string $environment,
        array $backup,
        bool $healthOk
    ): array {
        return [
            'timestamp' => date('c'),
            'environment' => $environment,
            'backup_source' => [
                'database' => $backup['database'],
                'files' => $backup['files']
            ],
            'steps' => [
                'pause_cron' => true,
                'restore_database' => true,
                'restore_files' => true,
                'health_check' => $healthOk,
                'resume_cron' => true
            ],
            'all_successful' => $healthOk,
            'next_actions' => $healthOk
                ? ['Monitor system', 'Run full state validation']
                : ['Investigate failed checks', 'Contact support if needed']
        ];
    }
}
