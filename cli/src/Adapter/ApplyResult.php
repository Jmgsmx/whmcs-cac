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
