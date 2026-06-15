<?php

namespace Whmcs\Adapter;

use Whmcs\LocalApiClient;

class ProductAdapter implements AdapterInterface
{
    private const COMPARABLE_FIELDS = ['name', 'slug', 'type', 'module', 'paytype', 'pricing'];

    private LocalApiClient $api;

    public function __construct(LocalApiClient $api)
    {
        $this->api = $api;
    }

    public function exportLive(): array
    {
        try {
            $result = $this->api->call('GetProducts');
            $products = $result['products']['product'] ?? [];
            if (!is_array($products)) {
                return [];
            }

            $normalized = [];
            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $normalizedProduct = $this->normalizeLiveProduct($product);
                $key = $normalizedProduct['slug'] ?? null;
                if ($key === null || $key === '') {
                    $key = isset($normalizedProduct['pid']) ? 'pid:' . $normalizedProduct['pid'] : null;
                }
                if ($key === null) {
                    continue;
                }

                $normalized[$key] = $normalizedProduct;
            }

            return $normalized;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function diff(array $desired, array $live): array
    {
        $diff = ['changed' => [], 'added' => [], 'removed' => []];

        foreach ($desired as $key => $product) {
            if (!isset($live[$key])) {
                $diff['added'][$key] = $product;
                continue;
            }

            $desiredComparable = $this->comparableProduct($product);
            $liveComparable = $this->comparableProduct($live[$key]);
            if ($desiredComparable !== $liveComparable) {
                $diff['changed'][$key] = [
                    'desired' => $desiredComparable,
                    'current' => $liveComparable,
                ];
            }
        }

        foreach ($live as $key => $product) {
            if (!isset($desired[$key])) {
                $diff['removed'][$key] = $product;
            }
        }

        return $diff;
    }

    public function apply(array $plan): ApplyResult
    {
        if (empty($plan['changed']) && empty($plan['added']) && empty($plan['removed'])) {
            return ApplyResult::success('No product changes needed');
        }

        if (!empty($plan['changed'])) {
            return ApplyResult::failure(
                'Product updates are not supported by the public WHMCS Product API',
                array_keys($plan['changed'])
            );
        }

        if (!empty($plan['removed'])) {
            return ApplyResult::failure(
                'Product removal is not supported by the public WHMCS Product API',
                array_keys($plan['removed'])
            );
        }

        $created = [];
        foreach ($plan['added'] as $key => $product) {
            $params = $this->buildAddParams($key, $product);
            if (!empty($params['errors'])) {
                return ApplyResult::failure("Product {$key} is missing required API identifiers", $params['errors']);
            }

            $result = $this->api->call('AddProduct', $params['params']);
            $created[$key] = $result['pid'] ?? null;
        }

        return ApplyResult::success('Created ' . count($created) . ' product(s)', ['created' => $created]);
    }

    public function verify(array $desired): VerificationResult
    {
        $live = $this->exportLive();
        $diff = $this->diff($desired, $live);

        if (empty($diff['changed']) && empty($diff['added']) && empty($diff['removed'])) {
            return VerificationResult::ok();
        }

        return VerificationResult::mismatch($diff, 'Products do not match desired state');
    }

    private function normalizeLiveProduct(array $product): array
    {
        $normalized = [];

        foreach (['pid', 'gid', 'name', 'slug', 'type', 'module', 'paytype'] as $field) {
            if (array_key_exists($field, $product)) {
                $normalized[$field] = $product[$field];
            }
        }

        if (isset($normalized['pid'])) {
            $normalized['pid'] = (int)$normalized['pid'];
        }
        if (isset($normalized['gid'])) {
            $normalized['gid'] = (int)$normalized['gid'];
        }
        if (isset($product['pricing']) && is_array($product['pricing'])) {
            $normalized['pricing'] = $this->normalizePricing($product['pricing']);
        }

        return $normalized;
    }

    private function comparableProduct(array $product): array
    {
        $comparable = [];

        foreach (self::COMPARABLE_FIELDS as $field) {
            if (!array_key_exists($field, $product)) {
                continue;
            }

            $comparable[$field] = $field === 'pricing' && is_array($product[$field])
                ? $this->normalizePricing($product[$field])
                : $product[$field];
        }

        return $comparable;
    }

    private function normalizePricing(array $pricing): array
    {
        $normalized = [];

        foreach ($pricing as $currency => $cycles) {
            if (!is_array($cycles)) {
                continue;
            }

            foreach ($cycles as $cycle => $amount) {
                if (in_array($cycle, ['prefix', 'suffix'], true)) {
                    continue;
                }

                $normalized[$currency][$cycle] = is_numeric($amount) ? (float)$amount : $amount;
            }
        }

        ksort($normalized);
        foreach ($normalized as &$cycles) {
            ksort($cycles);
        }
        unset($cycles);

        return $normalized;
    }

    private function buildAddParams(string $key, array $product): array
    {
        $errors = [];
        $gid = $product['gid'] ?? $product['group_id'] ?? null;
        if ($gid === null) {
            $errors[] = 'gid/group_id is required for AddProduct';
        }

        if (empty($product['name'])) {
            $errors[] = 'name is required for AddProduct';
        }

        $pricing = $product['pricing'] ?? [];
        foreach (array_keys($pricing) as $currency) {
            if (!is_int($currency) && !ctype_digit((string)$currency)) {
                $errors[] = "pricing currency {$currency} must be a WHMCS currency ID";
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $params = [
            'gid' => (int)$gid,
            'name' => $product['name'],
            'slug' => $product['slug'] ?? $key,
        ];

        foreach (['type', 'paytype', 'autosetup', 'module'] as $field) {
            if (isset($product[$field])) {
                $params[$field] = $product[$field];
            }
        }

        foreach (['hidden', 'tax'] as $field) {
            if (array_key_exists($field, $product)) {
                $params[$field] = (bool)$product[$field];
            }
        }

        if (isset($product['server_group_id'])) {
            $params['servergroupid'] = (int)$product['server_group_id'];
        }

        if (isset($product['welcome_email_id'])) {
            $params['welcomeemail'] = (int)$product['welcome_email_id'];
        }

        if (!empty($pricing)) {
            $params['pricing'] = $pricing;
        }

        for ($i = 1; $i <= 6; $i++) {
            $keyName = 'configoption' . $i;
            if (isset($product['module_config'][$keyName])) {
                $params[$keyName] = $product['module_config'][$keyName];
            }
        }

        return ['params' => $params];
    }
}
