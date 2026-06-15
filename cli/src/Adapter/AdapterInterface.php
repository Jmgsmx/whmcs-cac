<?php

namespace Whmcs\Adapter;

interface AdapterInterface
{
    /**
     * Export live state from WHMCS.
     */
    public function exportLive(): array;

    /**
     * Calculate diff between desired and live state.
     */
    public function diff(array $desired, array $live): array;

    /**
     * Apply desired state to WHMCS. Returns result with metadata.
     */
    public function apply(array $plan): ApplyResult;

    /**
     * Verify that desired state matches live state after apply.
     */
    public function verify(array $desired): VerificationResult;
}
