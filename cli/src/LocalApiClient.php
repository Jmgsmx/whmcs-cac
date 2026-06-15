<?php

namespace Whmcs;

class LocalApiClient
{
    private string $whmcsRoot;
    private string $adminUsername;

    public function __construct(string $whmcsRoot, string $adminUsername)
    {
        $this->whmcsRoot = $whmcsRoot;
        $this->adminUsername = $adminUsername;
    }

    public function boot(): void
    {
        $init = rtrim($this->whmcsRoot, '/') . '/init.php';
        if (!file_exists($init)) {
            throw new \RuntimeException("WHMCS init.php not found: {$init}");
        }
        require_once $init;
    }

    public function call(string $command, array $params = []): array
    {
        $result = \localAPI($command, $params, $this->adminUsername);

        if (!is_array($result)) {
            throw new \RuntimeException("Invalid Local API response for {$command}");
        }

        if (($result['result'] ?? 'success') === 'error') {
            $message = $result['message'] ?? 'Unknown WHMCS API error';
            throw new \RuntimeException("WHMCS {$command} failed: {$message}");
        }

        return $result;
    }
}
