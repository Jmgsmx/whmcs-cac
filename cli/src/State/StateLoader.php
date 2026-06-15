<?php

namespace Whmcs\State;

use Symfony\Component\Yaml\Yaml;

class StateLoader
{
    public static function loadEnv(string $envPath): array
    {
        $state = [];

        $settingsFile = self::path($envPath, 'settings.yaml');
        if (file_exists($settingsFile)) {
            $state['settings'] = self::normalizeSettings(self::parseFile($settingsFile));
        }

        $gatewaysFile = self::path($envPath, 'gateways.yaml');
        if (file_exists($gatewaysFile)) {
            $state['gateways'] = self::normalizeGateways(self::parseFile($gatewaysFile));
        }

        return $state;
    }

    public static function parseFile(string $file): array
    {
        if (function_exists('yaml_parse_file')) {
            $parsed = yaml_parse_file($file);
            return is_array($parsed) ? $parsed : [];
        }

        $parsed = Yaml::parseFile($file);
        return is_array($parsed) ? $parsed : [];
    }

    public static function normalizeSettings(array $document): array
    {
        $settings = $document['settings'] ?? $document;
        $normalized = [];

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $settingKey => $settingValue) {
                    $normalized[$settingKey] = $settingValue;
                }
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    public static function normalizeGateways(array $document): array
    {
        $gateways = $document['gateways'] ?? $document;
        $normalized = [];

        foreach ($gateways as $key => $gateway) {
            if (!is_array($gateway)) {
                continue;
            }

            $gatewayKey = $gateway['key'] ?? (is_string($key) ? $key : null);
            if ($gatewayKey === null || $gatewayKey === '') {
                continue;
            }

            $normalized[$gatewayKey] = [
                'enabled' => (bool)($gateway['enabled'] ?? false),
                'visible' => (bool)($gateway['visible'] ?? false),
                'settings' => $gateway['settings'] ?? [],
            ];
        }

        return $normalized;
    }

    private static function path(string $envPath, string $file): string
    {
        return rtrim($envPath, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $file;
    }
}
