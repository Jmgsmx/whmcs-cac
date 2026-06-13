<?php

namespace Whmcs\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Whmcs\Adapter\SettingsAdapter;
use Whmcs\Adapter\ApplyResult;

class SettingsAdapterTest extends TestCase
{
    public function testExportLiveReturnsEmptyArrayOnError()
    {
        // Mock API that throws exception
        $apiMock = $this->createMock(\Whmcs\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->willThrowException(new \Exception('API error'));

        $adapter = new SettingsAdapter($apiMock);
        $result = $adapter->exportLive();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDiffDetectsChanges()
    {
        $apiMock = $this->createMock(\Whmcs\Whmcs\LocalApiClient::class);
        $adapter = new SettingsAdapter($apiMock);

        $desired = ['CompanyName' => 'JMGS', 'SystemURL' => 'https://example.com'];
        $live = ['CompanyName' => 'Old Corp', 'SystemURL' => 'https://example.com'];

        $diff = $adapter->diff($desired, $live);

        $this->assertArrayHasKey('changed', $diff);
        $this->assertArrayHasKey('CompanyName', $diff['changed']);
        $this->assertEquals('JMGS', $diff['changed']['CompanyName']['desired']);
    }

    public function testApplyReturnsSuccessWhenNoDiff()
    {
        $apiMock = $this->createMock(\Whmcs\Whmcs\LocalApiClient::class);
        $adapter = new SettingsAdapter($apiMock);

        $plan = ['changed' => [], 'added' => [], 'removed' => []];
        $result = $adapter->apply($plan);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('No changes needed', $result->message);
    }

    public function testVerifyOkWhenStateMatches()
    {
        $apiMock = $this->createMock(\Whmcs\Whmcs\LocalApiClient::class);
        $apiMock->method('call')->willReturn(['settings' => ['CompanyName' => 'JMGS']]);

        $adapter = new SettingsAdapter($apiMock);
        $desired = ['CompanyName' => 'JMGS'];

        $result = $adapter->verify($desired);

        $this->assertTrue($result->valid);
    }
}
