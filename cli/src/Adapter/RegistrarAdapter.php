<?php

namespace Whmcs\Adapter;

use Whmcs\LocalApiClient;

class RegistrarAdapter implements AdapterInterface
{
    private LocalApiClient $api;

    public function __construct(LocalApiClient $api)
    {
        $this->api = $api;
    }

    public function exportLive(): array
    {
        try {
            $result = $this->api->call('GetRegistrars');
            $registrars = $result['registrars'] ?? $result;
            if (!is_array($registrars)) {
                return [];
            }

            $normalized = [];
            foreach ($registrars as $key => $registrar) {
                $registrarKey = $this->registrarKey($key, $registrar);
                if ($registrarKey === null) {
                    continue;
                }

                $normalized[$registrarKey] = ['enabled' => true];
            }

            return $normalized;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function diff(array $desired, array $live): array
    {
        $diff = ['changed' => [], 'added' => [], 'removed' => []];

        foreach ($desired as $key => $registrar) {
            $desiredComparable = ['enabled' => (bool)($registrar['enabled'] ?? false)];
            $liveComparable = $live[$key] ?? null;

            if ($liveComparable === null) {
                if ($desiredComparable['enabled']) {
                    $diff['added'][$key] = $desiredComparable;
                }
                continue;
            }

            if ($desiredComparable !== ['enabled' => (bool)($liveComparable['enabled'] ?? false)]) {
                $diff['changed'][$key] = [
                    'desired' => $desiredComparable,
                    'current' => ['enabled' => (bool)($liveComparable['enabled'] ?? false)],
                ];
            }
        }

        foreach ($live as $key => $registrar) {
            if (!isset($desired[$key])) {
                $diff['removed'][$key] = $registrar;
            }
        }

        return $diff;
    }

    public function apply(array $plan): ApplyResult
    {
        if (empty($plan['changed']) && empty($plan['added']) && empty($plan['removed'])) {
            return ApplyResult::success('No registrar changes needed');
        }

        return ApplyResult::failure(
            'Registrar configuration is read-only via the public WHMCS API; configure registrar modules before applying TLDs',
            [
                'changed' => array_keys($plan['changed'] ?? []),
                'added' => array_keys($plan['added'] ?? []),
                'removed' => array_keys($plan['removed'] ?? []),
            ]
        );
    }

    public function verify(array $desired): VerificationResult
    {
        $live = $this->exportLive();
        $diff = $this->diff($desired, $live);

        if (empty($diff['changed']) && empty($diff['added']) && empty($diff['removed'])) {
            return VerificationResult::ok();
        }

        return VerificationResult::mismatch($diff, 'Registrars do not match desired state');
    }

    private function registrarKey(int|string $key, mixed $registrar): ?string
    {
        if (is_string($key) && $key !== '' && !is_numeric($key)) {
            return $key;
        }

        if (is_string($registrar) && $registrar !== '') {
            return $registrar;
        }

        if (is_array($registrar)) {
            foreach (['module', 'registrar', 'name', 'key'] as $field) {
                if (!empty($registrar[$field]) && is_string($registrar[$field])) {
                    return $registrar[$field];
                }
            }
        }

        return null;
    }
}
