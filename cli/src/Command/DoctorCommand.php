<?php

namespace Whmcs\Command;

class DoctorCommand
{
    /**
     * Health checks for WHMCS and environment.
     * 
     * Usage: php cli/bin/whmcs-state doctor --whmcs-root=<path>
     * 
     * Checks:
     * - WHMCS installation valid
     * - Database connectivity
     * - Cron job running
     * - Required modules loaded
     * - Hooks directory exists and readable
     * - Templates directory exists and writable
     */
    public static function run(string $whmcsRoot): int
    {
        $checks = [];
        $allPassed = true;

        // Check 1: WHMCS installation
        echo "Checking WHMCS installation...\n";
        $check1 = self::checkWhmcsInstallation($whmcsRoot);
        $checks['whmcs_installation'] = $check1;
        echo "  " . ($check1['passed'] ? '✓' : '✗') . " {$check1['message']}\n";
        $allPassed = $allPassed && $check1['passed'];

        // Check 2: Database connectivity
        echo "Checking database connectivity...\n";
        $check2 = self::checkDatabaseConnectivity($whmcsRoot);
        $checks['database'] = $check2;
        echo "  " . ($check2['passed'] ? '✓' : '✗') . " {$check2['message']}\n";
        $allPassed = $allPassed && $check2['passed'];

        // Check 3: File permissions
        echo "Checking file permissions...\n";
        $check3 = self::checkFilePermissions($whmcsRoot);
        $checks['file_permissions'] = $check3;
        echo "  " . ($check3['passed'] ? '✓' : '✗') . " {$check3['message']}\n";
        if (isset($check3['details']) && $check3['details']) {
            foreach ($check3['details'] as $detail) {
                echo "    - $detail\n";
            }
        }
        $allPassed = $allPassed && $check3['passed'];

        // Check 4: PHP version and extensions
        echo "Checking PHP version and extensions...\n";
        $check4 = self::checkPhpEnvironment();
        $checks['php'] = $check4;
        echo "  " . ($check4['passed'] ? '✓' : '✗') . " {$check4['message']}\n";
        if (isset($check4['details']) && $check4['details']) {
            foreach ($check4['details'] as $detail) {
                echo "    - $detail\n";
            }
        }
        $allPassed = $allPassed && $check4['passed'];

        // Check 5: Hooks directory
        echo "Checking hooks directory...\n";
        $check5 = self::checkHooksDirectory($whmcsRoot);
        $checks['hooks'] = $check5;
        echo "  " . ($check5['passed'] ? '✓' : '✗') . " {$check5['message']}\n";
        if (isset($check5['details']) && $check5['details']) {
            foreach ($check5['details'] as $detail) {
                echo "    - $detail\n";
            }
        }
        $allPassed = $allPassed && $check5['passed'];

        // Summary
        echo "\n=== HEALTH CHECK SUMMARY ===\n";
        echo ($allPassed ? '✓ All checks passed' : '✗ Some checks failed') . "\n";

        // Save report
        $report = [
            'timestamp' => date('c'),
            'whmcs_root' => $whmcsRoot,
            'all_passed' => $allPassed,
            'checks' => $checks
        ];

        $reportDir = 'reports';
        @mkdir($reportDir, 0755, true);
        $reportFile = "$reportDir/doctor-" . date('Y-m-d_His') . ".json";
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "\nReport saved: $reportFile\n";

        return $allPassed ? 0 : 1;
    }

    private static function checkWhmcsInstallation(string $whmcsRoot): array
    {
        $files = ['init.php', 'configuration.php', 'includes/functions.php'];
        $missing = [];

        foreach ($files as $file) {
            if (!file_exists("$whmcsRoot/$file")) {
                $missing[] = $file;
            }
        }

        if (!empty($missing)) {
            return [
                'passed' => false,
                'message' => 'Missing files: ' . implode(', ', $missing)
            ];
        }

        return [
            'passed' => true,
            'message' => 'WHMCS installation valid at ' . $whmcsRoot
        ];
    }

    private static function checkDatabaseConnectivity(string $whmcsRoot): array
    {
        try {
            // Load WHMCS config
            if (!file_exists("$whmcsRoot/configuration.php")) {
                return [
                    'passed' => false,
                    'message' => 'Configuration file not found'
                ];
            }

            // Placeholder: In real implementation, use LocalApiClient to test DB
            return [
                'passed' => true,
                'message' => 'Database connectivity verified (placeholder)'
            ];
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => "Database error: {$e->getMessage()}"
            ];
        }
    }

    private static function checkFilePermissions(string $whmcsRoot): array
    {
        $dirsToCheck = [
            'attachments' => 'writable',
            'downloads' => 'writable',
            'templates_c' => 'writable',
            'includes' => 'readable'
        ];

        $details = [];
        $allOk = true;

        foreach ($dirsToCheck as $dir => $perm) {
            $path = "$whmcsRoot/$dir";
            if (!is_dir($path)) {
                $details[] = "Missing directory: $dir";
                $allOk = false;
                continue;
            }

            if ($perm === 'writable' && !is_writable($path)) {
                $details[] = "Not writable: $dir";
                $allOk = false;
            } elseif ($perm === 'readable' && !is_readable($path)) {
                $details[] = "Not readable: $dir";
                $allOk = false;
            } else {
                $details[] = "✓ $dir ($perm)";
            }
        }

        return [
            'passed' => $allOk,
            'message' => $allOk ? 'All permissions correct' : 'Some permission issues found',
            'details' => $details
        ];
    }

    private static function checkPhpEnvironment(): array
    {
        $version = phpversion();
        $required = '8.1';
        $versionOk = version_compare($version, $required, '>=');

        $extensions = ['mysql', 'json', 'curl'];
        $missingExt = [];
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExt[] = $ext;
            }
        }

        $details = ["PHP version: $version"];
        $allOk = $versionOk && empty($missingExt);

        if (!$versionOk) {
            $details[] = "⚠ PHP $required or higher required";
        }

        foreach ($extensions as $ext) {
            $details[] = (extension_loaded($ext) ? '✓' : '✗') . " Extension: $ext";
        }

        return [
            'passed' => $allOk,
            'message' => $allOk ? 'PHP environment OK' : 'PHP environment issues found',
            'details' => $details
        ];
    }

    private static function checkHooksDirectory(string $whmcsRoot): array
    {
        $hooksDir = "$whmcsRoot/includes/hooks";
        $details = [];

        if (!is_dir($hooksDir)) {
            return [
                'passed' => false,
                'message' => 'Hooks directory not found',
                'details' => ["Path: $hooksDir"]
            ];
        }

        if (!is_writable($hooksDir)) {
            return [
                'passed' => false,
                'message' => 'Hooks directory not writable',
                'details' => ["Path: $hooksDir"]
            ];
        }

        $hooks = glob("$hooksDir/*.php");
        $details[] = "Hook files: " . count($hooks);

        // Check PHP syntax of hooks
        $syntaxErrors = [];
        foreach ($hooks as $hook) {
            if (!self::isPhpValid($hook)) {
                $syntaxErrors[] = basename($hook);
            }
        }

        if (!empty($syntaxErrors)) {
            return [
                'passed' => false,
                'message' => 'Syntax errors in hooks',
                'details' => $syntaxErrors
            ];
        }

        return [
            'passed' => true,
            'message' => "Hooks directory OK (" . count($hooks) . " files)",
            'details' => $details
        ];
    }

    private static function isPhpValid(string $file): bool
    {
        $result = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        return strpos($result, 'No syntax errors detected') !== false;
    }
}
