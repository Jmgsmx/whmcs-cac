<?php

namespace Whmcs\Command;

use Whmcs\Adapter\SettingsAdapter;
use Whmcs\Adapter\GatewayAdapter;

class DiffCommand
{
    /**
     * Compare desired state (YAML) with live state (WHMCS).
     * 
     * Usage: php cli/bin/whmcs-state diff --env=<path> [--resource=all|settings|gateways]
     */
    public static function run(string $envPath, string $whmcsRoot, string $resource = 'all'): int
    {
        try {
            // Verify WHMCS installation
            if (!file_exists("$whmcsRoot/init.php")) {
                error_log("Error: WHMCS init.php not found at $whmcsRoot");
                return 1;
            }

            // Connect to WHMCS
            $apiClient = new \Whmcs\Whmcs\LocalApiClient($whmcsRoot, 'admin');

            $diffs = [];
            $hasChanges = false;

            // Load YAML state files
            $stateFile = "$envPath/whmcs-state.yaml";
            if (!file_exists($stateFile)) {
                error_log("Error: State file not found: $stateFile");
                return 1;
            }

            // Check Settings
            if ($resource === 'all' || $resource === 'settings') {
                echo "Comparing settings...\n";
                $settingsFile = "$envPath/settings.yaml";
                if (file_exists($settingsFile)) {
                    $desiredSettings = yaml_parse_file($settingsFile);
                    $settingsAdapter = new SettingsAdapter($apiClient);
                    $liveSettings = $settingsAdapter->exportLive();
                    $diff = $settingsAdapter->diff($desiredSettings ?? [], $liveSettings);
                    
                    if (!empty($diff['changed']) || !empty($diff['added']) || !empty($diff['removed'])) {
                        $diffs['settings'] = $diff;
                        $hasChanges = true;
                    }
                }
            }

            // Check Gateways
            if ($resource === 'all' || $resource === 'gateways') {
                echo "Comparing gateways...\n";
                $gatewaysFile = "$envPath/gateways.yaml";
                if (file_exists($gatewaysFile)) {
                    $desiredGateways = yaml_parse_file($gatewaysFile);
                    $gatewayAdapter = new GatewayAdapter($apiClient);
                    $liveGateways = $gatewayAdapter->exportLive();
                    $diff = $gatewayAdapter->diff($desiredGateways ?? [], $liveGateways);
                    
                    if (!empty($diff['changed']) || !empty($diff['added']) || !empty($diff['removed'])) {
                        $diffs['gateways'] = $diff;
                        $hasChanges = true;
                    }
                }
            }

            if ($hasChanges) {
                self::displayDiff($diffs);
                return 1; // Exit with 1 to indicate changes exist
            } else {
                echo "✓ No differences found. Desired state matches live state.\n";
                return 0;
            }
        } catch (\Exception $e) {
            error_log("diff failed: {$e->getMessage()}");
            return 1;
        }
    }

    private static function displayDiff(array $diffs): void
    {
        foreach ($diffs as $resource => $diff) {
            echo "\n=== $resource ===\n";
            
            if (!empty($diff['changed'])) {
                echo "\n[CHANGED]\n";
                foreach ($diff['changed'] as $key => $change) {
                    echo "  - $key:\n";
                    echo "      current:  " . json_encode($change['current']) . "\n";
                    echo "      desired:  " . json_encode($change['desired']) . "\n";
                }
            }

            if (!empty($diff['added'])) {
                echo "\n[ADDED]\n";
                foreach ($diff['added'] as $key => $value) {
                    echo "  + $key: " . json_encode($value) . "\n";
                }
            }

            if (!empty($diff['removed'])) {
                echo "\n[REMOVED]\n";
                foreach ($diff['removed'] as $key => $value) {
                    echo "  - $key: " . json_encode($value) . "\n";
                }
            }
        }
    }
}
