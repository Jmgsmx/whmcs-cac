<?php

namespace Whmcs\Adapter;

use Whmcs\LocalApiClient;

class SettingsAdapter implements AdapterInterface
{
    private LocalApiClient $api;

    public function __construct(LocalApiClient $api)
    {
        $this->api = $api;
    }

    public function exportLive(): array
    {
        try {
            $result = $this->api->call('GetSettings');
            return $result['settings'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function diff(array $desired, array $live): array
    {
        $diff = ['changed' => [], 'added' => [], 'removed' => []];

        foreach ($desired as $key => $value) {
            if (!isset($live[$key])) {
                $diff['added'][$key] = $value;
            } elseif ($live[$key] !== $value) {
                $diff['changed'][$key] = ['desired' => $value, 'current' => $live[$key]];
            }
        }

        foreach ($live as $key => $value) {
            if (!isset($desired[$key])) {
                $diff['removed'][$key] = $value;
            }
        }

        return $diff;
    }

    public function apply(array $plan): ApplyResult
    {
        if (empty($plan['changed']) && empty($plan['added']) && empty($plan['removed'])) {
            return ApplyResult::success('No changes needed');
        }

        try {
            foreach ($plan['changed'] + $plan['added'] as $key => $value) {
                $this->api->call('UpdateClientProduct', ['setting' => $key, 'value' => $value]);
            }
            return ApplyResult::success("Applied {$this->countChanges($plan)} changes");
        } catch (\Exception $e) {
            return ApplyResult::failure("Apply failed: {$e->getMessage()}");
        }
    }

    public function verify(array $desired): VerificationResult
    {
        $live = $this->exportLive();
        $diff = $this->diff($desired, $live);

        if (empty($diff['changed']) && empty($diff['added']) && empty($diff['removed'])) {
            return VerificationResult::ok();
        }

        return VerificationResult::mismatch($diff, 'Settings do not match desired state');
    }

    private function countChanges(array $plan): int
    {
        return count($plan['changed'] ?? []) + count($plan['added'] ?? []) + count($plan['removed'] ?? []);
    }
}
