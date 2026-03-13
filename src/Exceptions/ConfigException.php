<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Exceptions;

use RuntimeException;

class ConfigException extends RuntimeException
{
    public static function fileNotFound(string $path): self
    {
        return new self("Config file not found: '{$path}'.");
    }

    public static function directoryNotFound(string $dir): self
    {
        return new self("Config directory not found: '{$dir}'.");
    }

    public static function unsupportedFormat(string $extension): self
    {
        return new self("Unsupported config format: '{$extension}'. Supported: php, json.");
    }

    public static function invalidFormat(string $path, string $reason): self
    {
        return new self("Invalid config file '{$path}': {$reason}.");
    }

    public static function readFailed(string $path): self
    {
        return new self("Failed to read config file: '{$path}'.");
    }
}
