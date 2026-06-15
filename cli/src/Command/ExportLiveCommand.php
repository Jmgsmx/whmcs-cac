<?php

namespace Whmcs\Command;

use Whmcs\Adapter\SettingsAdapter;
use Whmcs\Adapter\GatewayAdapter;
use Whmcs\Adapter\ProductAdapter;
use Whmcs\Adapter\RegistrarAdapter;
use Whmcs\Adapter\TldAdapter;

class ExportLiveCommand
{
    /**
     * Export live state from WHMCS into JSON snapshot.
     * 
     * Usage: php cli/bin/whmcs-state export-live --env=<path> [--resource=all|settings|gateways|products|registrars|tlds]
     */
    public static function run(string $whmcsRoot, string $resource = 'all'): int
    {
        try {
            // Verify WHMCS installation
            if (!file_exists("$whmcsRoot/init.php")) {
                error_log("Error: WHMCS init.php not found at $whmcsRoot");
                return 1;
            }

            // Connect to WHMCS
            $apiClient = new \Whmcs\LocalApiClient($whmcsRoot, 'admin');

            $liveState = [
                'timestamp' => date('c'),
                'resources' => []
            ];

            // Export Settings
            if ($resource === 'all' || $resource === 'settings') {
                echo "Exporting settings...\n";
                $settingsAdapter = new SettingsAdapter($apiClient);
                $liveState['resources']['settings'] = $settingsAdapter->exportLive();
            }

            // Export Gateways
            if ($resource === 'all' || $resource === 'gateways') {
                echo "Exporting payment gateways...\n";
                $gatewayAdapter = new GatewayAdapter($apiClient);
                $liveState['resources']['gateways'] = $gatewayAdapter->exportLive();
            }

            // Export Products
            if ($resource === 'all' || $resource === 'products') {
                echo "Exporting products...\n";
                $productAdapter = new ProductAdapter($apiClient);
                $liveState['resources']['products'] = $productAdapter->exportLive();
            }

            // Export Registrars
            if ($resource === 'all' || $resource === 'registrars') {
                echo "Exporting registrars...\n";
                $registrarAdapter = new RegistrarAdapter($apiClient);
                $liveState['resources']['registrars'] = $registrarAdapter->exportLive();
            }

            // Export TLDs
            if ($resource === 'all' || $resource === 'tlds') {
                echo "Exporting TLD pricing...\n";
                $tldAdapter = new TldAdapter($apiClient);
                $liveState['resources']['tlds'] = $tldAdapter->exportLive();
            }

            // Write to state/live/snapshots/{timestamp}.json
            $snapshotDir = 'state/live/snapshots';
            @mkdir($snapshotDir, 0755, true);
            
            $timestamp = date('Y-m-d_His');
            $snapshotFile = "$snapshotDir/export-live-$timestamp.json";
            
            if (file_put_contents($snapshotFile, json_encode($liveState, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                error_log("Error: Could not write snapshot to $snapshotFile");
                return 1;
            }

            echo "✓ Snapshot saved: $snapshotFile\n";
            echo "  Resources: " . implode(', ', array_keys($liveState['resources'])) . "\n";
            return 0;
        } catch (\Exception $e) {
            error_log("export-live failed: {$e->getMessage()}");
            return 1;
        }
    }
}
