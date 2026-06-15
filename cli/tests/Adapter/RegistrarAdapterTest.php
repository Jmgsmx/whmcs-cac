<?php

namespace Whmcs\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Whmcs\Adapter\RegistrarAdapter;

class RegistrarAdapterTest extends TestCase
{
    public function testExportLiveIndexesActiveRegistrars(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->with('GetRegistrars')->willReturn([
            'registrars' => [
                ['module' => 'resellerclub'],
                'enom',
            ],
        ]);

        $adapter = new RegistrarAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertSame([
            'resellerclub' => ['enabled' => true],
            'enom' => ['enabled' => true],
        ], $result);
    }

    public function testDiffIgnoresDisabledMissingRegistrars(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $adapter = new RegistrarAdapter($apiMock);

        $diff = $adapter->diff([
            'resellerclub' => ['enabled' => false],
        ], []);

        $this->assertEmpty($diff['added']);
        $this->assertEmpty($diff['changed']);
        $this->assertEmpty($diff['removed']);
    }

    public function testApplyFailsWhenRegistrarChangesAreRequired(): void
    {
        $apiMock = $this->createMock(\Whmcs\LocalApiClient::class);
        $apiMock->expects($this->never())->method('call');

        $adapter = new RegistrarAdapter($apiMock);
        $result = $adapter->apply([
            'changed' => [],
            'added' => ['resellerclub' => ['enabled' => true]],
            'removed' => [],
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('read-only', $result->message);
    }
}
