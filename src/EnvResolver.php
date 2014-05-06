<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader;

final class EnvResolver
{
    /**
     * Recursively resolve ${VAR} placeholders in config values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolve(array $data): array
    {
        $resolved = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $resolved[$key] = self::resolve($value);
            } elseif (is_string($value)) {
                $resolved[$key] = self::resolveString($value);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Replace ${VAR} or ${VAR:default} placeholders in a string.
     */
    private static function resolveString(string $value): string
    {
        return preg_replace_callback('/\$\{([^}:]+)(?::([^}]*))?\}/', function (array $matches): string {
            $envVar = $matches[1];
            $default = $matches[2] ?? '';
            $envValue = getenv($envVar);

            return $envValue !== false ? $envValue : $default;
        }, $value) ?? $value;
    }
}
