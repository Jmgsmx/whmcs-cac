<?php

namespace Whmcs\Adapter;

class VerificationResult
{
    public function __construct(
        public bool $valid,
        public array $mismatches = [],
        public string $message = ''
    ) {}

    public static function ok(): self
    {
        return new self(true, [], 'State matches desired');
    }

    public static function mismatch(array $mismatches, string $message = ''): self
    {
        return new self(false, $mismatches, $message);
    }
}
