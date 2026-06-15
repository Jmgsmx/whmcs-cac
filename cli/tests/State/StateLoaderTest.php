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
        $this->assertSame('JMGS Hosting', $state['settings']['CompanyName']);
        $this->assertArrayHasKey('banktransfer', $state['gateways']);
        $this->assertArrayHasKey('stripe', $state['gateways']);
        $this->assertArrayHasKey('shared-starter', $state['products']);
        $this->assertSame('shared-starter', $state['products']['shared-starter']['slug']);
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
}
