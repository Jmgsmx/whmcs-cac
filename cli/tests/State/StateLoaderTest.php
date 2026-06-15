<?php

namespace Whmcs\Tests\State;

use PHPUnit\Framework\TestCase;
use Whmcs\State\StateLoader;

class StateLoaderTest extends TestCase
{
    public function testNormalizeSettingsFlattensSections(): void
    {
        $settings = StateLoader::normalizeSettings([
            'settings' => [
                'system' => [
                    'CompanyName' => 'JMGS Hosting',
                    'SystemURL' => 'https://billing-dev.example.com',
                ],
                'ordering' => [
                    'DefaultOrderFormTemplate' => 'standard_cart',
                ],
            ],
        ]);

        $this->assertSame([
            'CompanyName' => 'JMGS Hosting',
            'SystemURL' => 'https://billing-dev.example.com',
            'DefaultOrderFormTemplate' => 'standard_cart',
        ], $settings);
    }

    public function testNormalizeGatewaysConvertsListToGatewayMap(): void
    {
        $gateways = StateLoader::normalizeGateways([
            'gateways' => [
                [
                    'key' => 'banktransfer',
                    'management_mode' => 'api',
                    'enabled' => true,
                    'visible' => true,
                    'display_name' => 'Transferencia bancaria',
                    'settings' => [
                        'FriendlyName' => 'Transferencia bancaria',
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            'banktransfer' => [
                'enabled' => true,
                'visible' => true,
                'settings' => [
                    'FriendlyName' => 'Transferencia bancaria',
                ],
            ],
        ], $gateways);
    }

    public function testLoadEnvParsesConfiguredSettingsAndGateways(): void
    {
        $state = StateLoader::loadEnv(WHMCS_CAC_ROOT . '/state/envs/dev');

        $this->assertArrayHasKey('settings', $state);
        $this->assertArrayHasKey('gateways', $state);
        $this->assertArrayHasKey('products', $state);
        $this->assertArrayHasKey('registrars', $state);
        $this->assertArrayHasKey('tlds', $state);
        $this->assertArrayHasKey('product_groups', $state);
        $this->assertArrayHasKey('server_groups', $state);
        $this->assertArrayHasKey('servers', $state);
        $this->assertSame('JMGS Hosting', $state['settings']['CompanyName']);
        $this->assertArrayHasKey('banktransfer', $state['gateways']);
        $this->assertArrayHasKey('stripe', $state['gateways']);
        $this->assertArrayHasKey('shared-starter', $state['products']);
        $this->assertSame('shared-starter', $state['products']['shared-starter']['slug']);
        $this->assertArrayHasKey('resellerclub', $state['registrars']);
        $this->assertArrayHasKey('.com', $state['tlds']);
        $this->assertArrayHasKey('shared-hosting', $state['product_groups']);
        $this->assertArrayHasKey('cpanel-mx', $state['server_groups']);
        $this->assertArrayHasKey('cpanel-01', $state['servers']);
    }

    public function testNormalizeProductsConvertsListToProductMap(): void
    {
        $products = StateLoader::normalizeProducts([
            'products' => [
                [
                    'key' => 'shared-starter',
                    'management_mode' => 'api',
                    'name' => 'Shared Starter',
                ],
            ],
        ]);

        $this->assertSame([
            'shared-starter' => [
                'management_mode' => 'api',
                'name' => 'Shared Starter',
                'slug' => 'shared-starter',
            ],
        ], $products);
    }

    public function testNormalizeRegistrarsConvertsListToRegistrarMap(): void
    {
        $registrars = StateLoader::normalizeRegistrars([
            'registrars' => [
                [
                    'key' => 'resellerclub',
                    'enabled' => false,
                    'settings' => ['testmode' => true],
                ],
            ],
        ]);

        $this->assertSame([
            'resellerclub' => [
                'enabled' => false,
                'settings' => ['testmode' => true],
            ],
        ], $registrars);
    }

    public function testNormalizeTldsConvertsListToExtensionMap(): void
    {
        $tlds = StateLoader::normalizeTlds([
            'tlds' => [
                [
                    'extension' => 'COM',
                    'dns_management' => true,
                    'email_forwarding' => false,
                    'id_protection' => true,
                    'currency' => 'MXN',
                    'register' => [1 => 279.0],
                ],
            ],
        ]);

        $this->assertSame([
            '.com' => [
                'extension' => '.com',
                'dns_management' => true,
                'email_forwarding' => false,
                'id_protection' => true,
                'currency' => 'MXN',
                'register' => [1 => 279.0],
            ],
        ], $tlds);
    }

    public function testNormalizeProductGroupsConvertsListToGroupMap(): void
    {
        $groups = StateLoader::normalizeProductGroups([
            'product_groups' => [
                [
                    'key' => 'shared-hosting',
                    'management_mode' => 'ui',
                    'name' => 'Shared Hosting',
                ],
            ],
        ]);

        $this->assertSame([
            'shared-hosting' => [
                'management_mode' => 'ui',
                'name' => 'Shared Hosting',
            ],
        ], $groups);
    }

    public function testNormalizeServerGroupsAndServersConvertListsToMaps(): void
    {
        $document = [
            'server_groups' => [
                [
                    'key' => 'cpanel-mx',
                    'management_mode' => 'ui',
                    'name' => 'cPanel MX',
                ],
            ],
            'servers' => [
                [
                    'key' => 'cpanel-01',
                    'management_mode' => 'ui',
                    'hostname' => 'whm01.example.com',
                ],
            ],
        ];

        $this->assertSame([
            'cpanel-mx' => [
                'management_mode' => 'ui',
                'name' => 'cPanel MX',
            ],
        ], StateLoader::normalizeServerGroups($document));

        $this->assertSame([
            'cpanel-01' => [
                'management_mode' => 'ui',
                'hostname' => 'whm01.example.com',
            ],
        ], StateLoader::normalizeServers($document));
    }
}
