<?php

namespace Whmcs\Adapter;

class ApplyResult
{
    public function __construct(
        public bool $success,
        public string $message = '',
        public array $data = [],
        public array $errors = []
    ) {}

    public static function success(string $message = '', array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, $message, [], $errors);
    }
}

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
