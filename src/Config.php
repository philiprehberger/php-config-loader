<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader;

final class Config
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * Get a value by dot-notation key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Get a string value.
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : $default;
    }

    /**
     * Get an integer value.
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : $default);
    }

    /**
     * Get a boolean value.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if (is_bool($value)) {
            return $value;
        }
        if ($value === '1' || $value === 'true' || $value === 1) {
            return true;
        }
        if ($value === '0' || $value === 'false' || $value === 0) {
            return false;
        }

        return $default;
    }

    /**
     * Get a float value.
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        return is_float($value) || is_int($value) ? (float) $value : (is_numeric($value) ? (float) $value : $default);
    }

    /**
     * Get an array value.
     *
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : $default;
    }

    /**
     * Check if a key exists (supports dot notation).
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Return all config data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Deep merge another config into this one. Returns a new Config.
     */
    public function merge(self $other): self
    {
        return new self(self::deepMerge($this->data, $other->data));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private static function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
