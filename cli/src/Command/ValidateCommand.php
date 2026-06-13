<?php

namespace Whmcs\Command;

class ValidateCommand
{
    public static function run(string $envPath): int
    {
        $file = rtrim($envPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'whmcs-state.yaml';

        if (!file_exists($file)) {
            echo "Missing state file: {$file}\n";
            return 2;
        }

        // Basic YAML readability check using yaml_parse_file if available
        if (function_exists('yaml_parse_file')) {
            $parsed = @yaml_parse_file($file);
            if ($parsed === false) {
                echo "YAML parse error: {$file}\n";
                return 3;
            }
            echo "YAML ok: {$file}\n";
            return 0;
        }

        echo "File exists: {$file} (no YAML parser available for deep validation)\n";
        return 0;
    }
}
