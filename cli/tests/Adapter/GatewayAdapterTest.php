<?php

namespace Whmcs\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Whmcs\Adapter\GatewayAdapter;

class GatewayAdapterTest extends TestCase
{
    public function testExportLiveNormalizesGatewayPayload(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->with('GetPaymentGateways')->willReturn([
            'gateways' => [
                'banktransfer' => [
                    'enabled' => 1,
                    'visible' => 0,
                    'settings' => ['FriendlyName' => 'Bank Transfer'],
                ],
            ],
        ]);

        $adapter = new GatewayAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertSame([
            'banktransfer' => [
                'enabled' => true,
                'visible' => false,
                'settings' => ['FriendlyName' => 'Bank Transfer'],
            ],
        ], $result);
    }

    public function testExportLiveReturnsEmptyArrayOnError(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->willThrowException(new \Exception('API error'));

        $adapter = new GatewayAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDiffDetectsGatewayChanges(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $adapter = new GatewayAdapter($apiMock);

        $desired = [
            'stripe' => [
                'enabled' => true,
                'visible' => true,
                'settings' => ['FriendlyName' => 'Card'],
            ],
        ];
        $live = [
            'stripe' => [
                'enabled' => false,
                'visible' => true,
                'settings' => ['FriendlyName' => 'Card'],
            ],
        ];

        $diff = $adapter->diff($desired, $live);

        $this->assertArrayHasKey('changed', $diff);
        $this->assertArrayHasKey('stripe', $diff['changed']);
        $this->assertTrue($diff['changed']['stripe']['desired']['enabled']);
    }

    public function testApplyCallsUpdatePaymentGatewayForChanges(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->once())
            ->method('call')
            ->with('UpdatePaymentGateway', [
                'gateway' => 'stripe',
                'enabled' => 1,
                'visible' => 1,
                'settings' => ['FriendlyName' => 'Card'],
            ])
            ->willReturn(['result' => 'success']);

        $adapter = new GatewayAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [
                'stripe' => [
                    'enabled' => true,
                    'visible' => true,
                    'settings' => ['FriendlyName' => 'Card'],
                ],
            ],
            'added' => [],
            'removed' => [],
        ]);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('Applied 1 gateway changes', $result->message);
    }

    public function testVerifyOkWhenStateMatches(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->willReturn([
            'gateways' => [
                'banktransfer' => [
                    'enabled' => true,
                    'visible' => true,
                    'settings' => [],
                ],
            ],
        ]);

        $adapter = new GatewayAdapter($apiMock);
        $desired = [
            'banktransfer' => [
                'enabled' => true,
                'visible' => true,
                'settings' => [],
            ],
        ];

        $result = $adapter->verify($desired);

        $this->assertTrue($result->valid);
    }
}
