<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Parsers;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;

final class TomlParser
{
    /**
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    public static function parse(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw ConfigException::readFailed($path);
        }

        return self::parseToml($content, $path);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    private static function parseToml(string $content, string $path): array
    {
        $data = [];
        $currentSection = &$data;
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Table header: [section] or [section.subsection]
            if (preg_match('/^\[([a-zA-Z0-9_.]+)\]$/', $line, $matches)) {
                $currentSection = &$data;
                $segments = explode('.', $matches[1]);

                foreach ($segments as $segment) {
                    $existing = $currentSection[$segment] ?? null;

                    if (! is_array($existing)) {
                        $currentSection[$segment] = [];
                    }

                    $currentSection = &$currentSection[$segment];
                }

                continue;
            }

            // Key-value pair
            if (preg_match('/^([a-zA-Z0-9_.-]+)\s*=\s*(.+)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $rawValue = trim($matches[2]);
                $currentSection[$key] = self::parseValue($rawValue, $path, $lineNum + 1);

                continue;
            }

            throw ConfigException::invalidFormat($path, 'Invalid TOML syntax at line '.($lineNum + 1).": {$line}");
        }

        return $data;
    }

    private static function parseValue(string $value, string $path, int $line): string|int|float|bool
    {
        // Boolean
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Quoted string (double or single)
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return stripcslashes($matches[1]);
        }
        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        // Float (must check before int since floats contain digits too)
        if (preg_match('/^[+-]?\d+\.\d+$/', $value)) {
            return (float) $value;
        }

        // Integer
        if (preg_match('/^[+-]?\d+$/', $value)) {
            return (int) $value;
        }

        throw ConfigException::invalidFormat($path, "Unsupported TOML value at line {$line}: {$value}");
    }
}
