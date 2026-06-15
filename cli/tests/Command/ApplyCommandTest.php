<?php

namespace Whmcs\Tests\Command;

use PHPUnit\Framework\TestCase;
use Whmcs\Command\ApplyCommand;

class ApplyCommandTest extends TestCase
{
    private string|false $previousReadOnly;

    protected function setUp(): void
    {
        $this->previousReadOnly = getenv('WHMCS_CAC_READ_ONLY');
    }

    protected function tearDown(): void
    {
        if ($this->previousReadOnly === false) {
            putenv('WHMCS_CAC_READ_ONLY');
            return;
        }

        putenv('WHMCS_CAC_READ_ONLY=' . $this->previousReadOnly);
    }

    public function testReadOnlyModeBlocksApplyBeforeWhmcsRootCheck(): void
    {
        putenv('WHMCS_CAC_READ_ONLY=1');

        $exit = ApplyCommand::run(
            WHMCS_CAC_ROOT . '/state/envs/dev',
            WHMCS_CAC_ROOT . '/missing-whmcs-root',
            true,
            false,
            'prod'
        );

        $this->assertSame(1, $exit);
    }
}
