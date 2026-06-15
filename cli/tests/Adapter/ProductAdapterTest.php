<?php

namespace Whmcs\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Whmcs\Adapter\ProductAdapter;

class ProductAdapterTest extends TestCase
{
    public function testExportLiveIndexesProductsBySlug(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->with('GetProducts')->willReturn([
            'products' => [
                'product' => [
                    [
                        'pid' => '12',
                        'gid' => '4',
                        'type' => 'hostingaccount',
                        'name' => 'Shared Starter',
                        'slug' => 'shared-starter',
                        'module' => 'cpanel',
                        'paytype' => 'recurring',
                        'pricing' => [
                            'MXN' => [
                                'prefix' => '$',
                                'monthly' => '149.00',
                                'annually' => '1490.00',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $adapter = new ProductAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertArrayHasKey('shared-starter', $result);
        $this->assertSame(12, $result['shared-starter']['pid']);
        $this->assertSame(149.0, $result['shared-starter']['pricing']['MXN']['monthly']);
        $this->assertArrayNotHasKey('prefix', $result['shared-starter']['pricing']['MXN']);
    }

    public function testDiffDetectsProductChangesOnComparableFields(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $adapter = new ProductAdapter($apiMock);

        $desired = [
            'shared-starter' => [
                'name' => 'Shared Starter',
                'slug' => 'shared-starter',
                'type' => 'hostingaccount',
                'module' => 'cpanel',
                'paytype' => 'recurring',
                'pricing' => ['MXN' => ['monthly' => 149.0]],
            ],
        ];
        $live = [
            'shared-starter' => [
                'name' => 'Shared Starter',
                'slug' => 'shared-starter',
                'type' => 'hostingaccount',
                'module' => 'cpanel',
                'paytype' => 'recurring',
                'pricing' => ['MXN' => ['monthly' => 199.0]],
            ],
        ];

        $diff = $adapter->diff($desired, $live);

        $this->assertArrayHasKey('shared-starter', $diff['changed']);
        $this->assertSame(149.0, $diff['changed']['shared-starter']['desired']['pricing']['MXN']['monthly']);
    }

    public function testApplyCreatesAddedProductWithResolvedApiIdentifiers(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->once())
            ->method('call')
            ->with('AddProduct', [
                'gid' => 4,
                'name' => 'Shared Starter',
                'slug' => 'shared-starter',
                'type' => 'hostingaccount',
                'paytype' => 'recurring',
                'autosetup' => 'payment',
                'module' => 'cpanel',
                'hidden' => false,
                'tax' => true,
                'pricing' => [1 => ['monthly' => 149.0]],
            ])
            ->willReturn(['result' => 'success', 'pid' => 12]);

        $adapter = new ProductAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [],
            'added' => [
                'shared-starter' => [
                    'group_id' => 4,
                    'name' => 'Shared Starter',
                    'slug' => 'shared-starter',
                    'type' => 'hostingaccount',
                    'module' => 'cpanel',
                    'paytype' => 'recurring',
                    'autosetup' => 'payment',
                    'hidden' => false,
                    'tax' => true,
                    'pricing' => [1 => ['monthly' => 149.0]],
                ],
            ],
            'removed' => [],
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(['shared-starter' => 12], $result->data['created']);
    }

    public function testApplyFailsWhenProductGroupIdIsNotResolved(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->never())->method('call');

        $adapter = new ProductAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [],
            'added' => [
                'shared-starter' => [
                    'group_key' => 'shared-hosting',
                    'name' => 'Shared Starter',
                    'pricing' => ['MXN' => ['monthly' => 149.0]],
                ],
            ],
            'removed' => [],
        ]);

        $this->assertFalse($result->success);
        $this->assertContains('gid/group_id is required for AddProduct', $result->errors);
        $this->assertContains('pricing currency MXN must be a WHMCS currency ID', $result->errors);
    }

    public function testApplyFailsForChangedProducts(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->never())->method('call');

        $adapter = new ProductAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => ['shared-starter' => []],
            'added' => [],
            'removed' => [],
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('updates are not supported', $result->message);
    }

    public function testVerifyOkWhenProductComparableStateMatches(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->willReturn([
            'products' => [
                'product' => [
                    [
                        'name' => 'Shared Starter',
                        'slug' => 'shared-starter',
                        'type' => 'hostingaccount',
                        'module' => 'cpanel',
                        'paytype' => 'recurring',
                        'pricing' => ['MXN' => ['monthly' => '149.00']],
                    ],
                ],
            ],
        ]);

        $adapter = new ProductAdapter($apiMock);
        $result = $adapter->verify([
            'shared-starter' => [
                'name' => 'Shared Starter',
                'slug' => 'shared-starter',
                'type' => 'hostingaccount',
                'module' => 'cpanel',
                'paytype' => 'recurring',
                'pricing' => ['MXN' => ['monthly' => 149.0]],
            ],
        ]);

        $this->assertTrue($result->valid);
    }
}
