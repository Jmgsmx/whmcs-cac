<?php

namespace Whmcs\Adapter;

use Whmcs\LocalApiClient;

class GatewayAdapter implements AdapterInterface
{
    private LocalApiClient $api;

    public function __construct(LocalApiClient $api)
    {
        $this->api = $api;
    }

    public function exportLive(): array
    {
        try {
            $result = $this->api->call('GetPaymentGateways');
            $gateways = [];
            foreach ($result['gateways'] ?? [] as $gwName => $gwData) {
                $gateways[$gwName] = [
                    'enabled' => (bool)($gwData['enabled'] ?? false),
                    'visible' => (bool)($gwData['visible'] ?? false),
                    'settings' => $gwData['settings'] ?? []
                ];
            }
            return $gateways;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function diff(array $desired, array $live): array
    {
        $diff = ['changed' => [], 'added' => [], 'removed' => []];

        foreach ($desired as $gwName => $gwConfig) {
            if (!isset($live[$gwName])) {
                $diff['added'][$gwName] = $gwConfig;
            } elseif ($live[$gwName] !== $gwConfig) {
                $diff['changed'][$gwName] = ['desired' => $gwConfig, 'current' => $live[$gwName]];
            }
        }

        foreach ($live as $gwName => $gwConfig) {
            if (!isset($desired[$gwName])) {
                $diff['removed'][$gwName] = $gwConfig;
            }
        }

        return $diff;
    }

    public function apply(array $plan): ApplyResult
    {
        if (empty($plan['changed']) && empty($plan['added']) && empty($plan['removed'])) {
            return ApplyResult::success('No gateway changes needed');
        }

        try {
            foreach ($plan['changed'] + $plan['added'] as $gwName => $gwConfig) {
                $params = [
                    'gateway' => $gwName,
                    'enabled' => (int)($gwConfig['enabled'] ?? 0),
                    'visible' => (int)($gwConfig['visible'] ?? 0)
                ];
                if (isset($gwConfig['settings'])) {
                    $params['settings'] = $gwConfig['settings'];
                }
                $this->api->call('UpdatePaymentGateway', $params);
            }
            return ApplyResult::success("Applied {$this->countChanges($plan)} gateway changes");
        } catch (\Exception $e) {
            return ApplyResult::failure("Gateway apply failed: {$e->getMessage()}");
        }
    }

    public function verify(array $desired): VerificationResult
    {
        $live = $this->exportLive();
        $diff = $this->diff($desired, $live);

        if (empty($diff['changed']) && empty($diff['added']) && empty($diff['removed'])) {
            return VerificationResult::ok();
        }

        return VerificationResult::mismatch($diff, 'Gateways do not match desired state');
    }

    private function countChanges(array $plan): int
    {
        return count($plan['changed'] ?? []) + count($plan['added'] ?? []) + count($plan['removed'] ?? []);
    }
}
