<?php

namespace Whmcs\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Whmcs\Adapter\TldAdapter;

class TldAdapterTest extends TestCase
{
    public function testExportLiveNormalizesTldPricing(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->with('GetTLDPricing')->willReturn([
            'pricing' => [
                'com' => [
                    'tld' => 'com',
                    'addons' => [
                        'dns' => true,
                        'email' => false,
                        'idprotect' => true,
                    ],
                    'register' => ['1' => '14.95'],
                    'renew' => ['1' => '15.95'],
                    'transfer' => ['1' => '12.95'],
                ],
            ],
        ]);

        $adapter = new TldAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertSame([
            '.com' => [
                'extension' => '.com',
                'dns_management' => true,
                'email_forwarding' => false,
                'id_protection' => true,
                'register' => [1 => 14.95],
                'renew' => [1 => 15.95],
                'transfer' => [1 => 12.95],
            ],
        ], $result);
    }

    public function testApplyCallsCreateOrUpdateTldForAddedTld(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->once())
            ->method('call')
            ->with('CreateOrUpdateTLD', [
                'extension' => '.com',
                'dns_management' => true,
                'email_forwarding' => true,
                'id_protection' => true,
                'auto_registrar' => 'resellerclub',
                'currency_code' => 'MXN',
                'register' => [1 => 279.0],
                'renew' => [1 => 299.0],
                'transfer' => [1 => 279.0],
            ])
            ->willReturn(['result' => 'success', 'id' => '1']);

        $adapter = new TldAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [],
            'added' => [
                '.com' => [
                    'extension' => '.com',
                    'dns_management' => true,
                    'email_forwarding' => true,
                    'id_protection' => true,
                    'auto_registrar' => 'resellerclub',
                    'currency' => 'MXN',
                    'register' => [1 => 279.0],
                    'renew' => [1 => 299.0],
                    'transfer' => [1 => 279.0],
                ],
            ],
            'removed' => [],
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(['.com' => '1'], $result->data['applied']);
    }

    public function testApplyFailsForRemovedTlds(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->never())->method('call');

        $adapter = new TldAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [],
            'added' => [],
            'removed' => ['.mx' => []],
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('removal is not supported', $result->message);
    }
}
