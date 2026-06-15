<?php

namespace Whmcs\Adapter;

use Whmcs\LocalApiClient;

class TldAdapter implements AdapterInterface
{
    private const COMPARABLE_FIELDS = [
        'dns_management',
        'email_forwarding',
        'id_protection',
        'register',
        'renew',
        'transfer',
    ];

    private LocalApiClient $api;

    public function __construct(LocalApiClient $api)
    {
        $this->api = $api;
    }

    public function exportLive(): array
    {
        try {
            $result = $this->api->call('GetTLDPricing');
            $pricing = $result['pricing'] ?? [];
            if (!is_array($pricing)) {
                return [];
            }

            $normalized = [];
            foreach ($pricing as $key => $tld) {
                if (!is_array($tld)) {
                    continue;
                }

                $extension = $this->normalizeExtension($tld['tld'] ?? (string)$key);
                $normalized[$extension] = [
                    'extension' => $extension,
                    'dns_management' => (bool)($tld['addons']['dns'] ?? false),
                    'email_forwarding' => (bool)($tld['addons']['email'] ?? false),
                    'id_protection' => (bool)($tld['addons']['idprotect'] ?? false),
                    'register' => $this->normalizePeriods($tld['register'] ?? []),
                    'renew' => $this->normalizePeriods($tld['renew'] ?? []),
                    'transfer' => $this->normalizePeriods($tld['transfer'] ?? []),
                ];
            }

            return $normalized;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function diff(array $desired, array $live): array
    {
        $diff = ['changed' => [], 'added' => [], 'removed' => []];

        foreach ($desired as $extension => $tld) {
            if (!isset($live[$extension])) {
                $diff['added'][$extension] = $tld;
                continue;
            }

            $desiredComparable = $this->comparableTld($tld);
            $liveComparable = $this->comparableTld($live[$extension]);
            if ($desiredComparable !== $liveComparable) {
                $diff['changed'][$extension] = [
                    'desired' => $desiredComparable,
                    'current' => $liveComparable,
                ];
            }
        }

        foreach ($live as $extension => $tld) {
            if (!isset($desired[$extension])) {
                $diff['removed'][$extension] = $tld;
            }
        }

        return $diff;
    }

    public function apply(array $plan): ApplyResult
    {
        if (empty($plan['changed']) && empty($plan['added']) && empty($plan['removed'])) {
            return ApplyResult::success('No TLD changes needed');
        }

        if (!empty($plan['removed'])) {
            return ApplyResult::failure(
                'TLD removal is not supported by the public WHMCS TLD API',
                array_keys($plan['removed'])
            );
        }

        $applied = [];
        foreach (($plan['changed'] ?? []) + ($plan['added'] ?? []) as $extension => $payload) {
            $tld = $payload['desired'] ?? $payload;
            $params = $this->buildCreateOrUpdateParams($extension, $tld);
            $result = $this->api->call('CreateOrUpdateTLD', $params);
            $applied[$extension] = $result['id'] ?? null;
        }

        return ApplyResult::success('Applied ' . count($applied) . ' TLD change(s)', ['applied' => $applied]);
    }

    public function verify(array $desired): VerificationResult
    {
        $live = $this->exportLive();
        $diff = $this->diff($desired, $live);

        if (empty($diff['changed']) && empty($diff['added']) && empty($diff['removed'])) {
            return VerificationResult::ok();
        }

        return VerificationResult::mismatch($diff, 'TLDs do not match desired state');
    }

    private function comparableTld(array $tld): array
    {
        $comparable = [];

        foreach (self::COMPARABLE_FIELDS as $field) {
            if (!array_key_exists($field, $tld)) {
                continue;
            }

            $comparable[$field] = in_array($field, ['register', 'renew', 'transfer'], true)
                ? $this->normalizePeriods($tld[$field])
                : (bool)$tld[$field];
        }

        return $comparable;
    }

    private function buildCreateOrUpdateParams(string $extension, array $tld): array
    {
        $params = [
            'extension' => $tld['extension'] ?? $extension,
            'dns_management' => (bool)($tld['dns_management'] ?? false),
            'email_forwarding' => (bool)($tld['email_forwarding'] ?? false),
            'id_protection' => (bool)($tld['id_protection'] ?? false),
        ];

        if (!empty($tld['auto_registrar'])) {
            $params['auto_registrar'] = $tld['auto_registrar'];
        }

        if (!empty($tld['currency'])) {
            $params['currency_code'] = $tld['currency'];
        }

        foreach (['register', 'renew', 'transfer'] as $field) {
            if (!empty($tld[$field]) && is_array($tld[$field])) {
                $params[$field] = $this->normalizePeriods($tld[$field]);
            }
        }

        return $params;
    }

    private function normalizePeriods(array $periods): array
    {
        $normalized = [];

        foreach ($periods as $years => $amount) {
            $normalized[(int)$years] = is_numeric($amount) ? (float)$amount : $amount;
        }

        ksort($normalized);
        return $normalized;
    }

    private function normalizeExtension(string $extension): string
    {
        $extension = strtolower(trim($extension));
        return str_starts_with($extension, '.') ? $extension : '.' . $extension;
    }
}
