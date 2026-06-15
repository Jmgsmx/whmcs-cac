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

        $productsFile = self::path($envPath, 'products.yaml');
        if (file_exists($productsFile)) {
            $products = self::parseFile($productsFile);
            $state['product_groups'] = self::normalizeProductGroups($products);
            $state['products'] = self::normalizeProducts($products);
        }

        $domainsFile = self::path($envPath, 'domains.yaml');
        if (file_exists($domainsFile)) {
            $domains = self::parseFile($domainsFile);
            $state['registrars'] = self::normalizeRegistrars($domains);
            $state['tlds'] = self::normalizeTlds($domains);
        }

        $serversFile = self::path($envPath, 'servers.yaml');
        if (file_exists($serversFile)) {
            $servers = self::parseFile($serversFile);
            $state['server_groups'] = self::normalizeServerGroups($servers);
            $state['servers'] = self::normalizeServers($servers);
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

    public static function normalizeProducts(array $document): array
    {
        $products = $document['products'] ?? $document;
        $normalized = [];

        foreach ($products as $key => $product) {
            if (!is_array($product)) {
                continue;
            }

            $productKey = $product['key'] ?? (is_string($key) ? $key : null);
            if ($productKey === null || $productKey === '') {
                continue;
            }

            $normalized[$productKey] = $product;
            $normalized[$productKey]['slug'] = $product['slug'] ?? $productKey;
            unset($normalized[$productKey]['key']);
        }

        return $normalized;
    }

    public static function normalizeProductGroups(array $document): array
    {
        $groups = $document['product_groups'] ?? $document;
        return self::normalizeKeyedList($groups);
    }

    public static function normalizeServerGroups(array $document): array
    {
        $groups = $document['server_groups'] ?? $document;
        return self::normalizeKeyedList($groups);
    }

    public static function normalizeServers(array $document): array
    {
        $servers = $document['servers'] ?? $document;
        return self::normalizeKeyedList($servers);
    }

    public static function normalizeRegistrars(array $document): array
    {
        $registrars = $document['registrars'] ?? $document;
        $normalized = [];

        foreach ($registrars as $key => $registrar) {
            if (!is_array($registrar)) {
                continue;
            }

            $registrarKey = $registrar['key'] ?? (is_string($key) ? $key : null);
            if ($registrarKey === null || $registrarKey === '') {
                continue;
            }

            $normalized[$registrarKey] = [
                'enabled' => (bool)($registrar['enabled'] ?? false),
            ];

            if (isset($registrar['settings'])) {
                $normalized[$registrarKey]['settings'] = $registrar['settings'];
            }
        }

        return $normalized;
    }

    public static function normalizeTlds(array $document): array
    {
        $tlds = $document['tlds'] ?? $document;
        $normalized = [];

        foreach ($tlds as $key => $tld) {
            if (!is_array($tld)) {
                continue;
            }

            $extension = $tld['extension'] ?? (is_string($key) ? $key : null);
            if ($extension === null || $extension === '') {
                continue;
            }

            $extension = self::normalizeExtension($extension);
            $normalized[$extension] = [
                'extension' => $extension,
                'dns_management' => (bool)($tld['dns_management'] ?? false),
                'email_forwarding' => (bool)($tld['email_forwarding'] ?? false),
                'id_protection' => (bool)($tld['id_protection'] ?? false),
            ];

            foreach (['auto_registrar', 'currency', 'register', 'renew', 'transfer'] as $field) {
                if (isset($tld[$field])) {
                    $normalized[$extension][$field] = $tld[$field];
                }
            }
        }

        return $normalized;
    }

    private static function normalizeExtension(string $extension): string
    {
        $extension = strtolower(trim($extension));
        return str_starts_with($extension, '.') ? $extension : '.' . $extension;
    }

    private static function normalizeKeyedList(array $items): array
    {
        $normalized = [];

        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemKey = $item['key'] ?? (is_string($key) ? $key : null);
            if ($itemKey === null || $itemKey === '') {
                continue;
            }

            $normalized[$itemKey] = $item;
            unset($normalized[$itemKey]['key']);
        }

        return $normalized;
    }

    private static function path(string $envPath, string $file): string
    {
        return rtrim($envPath, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $file;
    }
}
