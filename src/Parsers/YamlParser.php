<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Parsers;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;

final class YamlParser
{
    /**
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    public static function parse(string $path): array
    {
        if (! extension_loaded('yaml')) {
            throw ConfigException::invalidFormat($path, 'The ext-yaml PHP extension is required to parse YAML files. Install it via: pecl install yaml');
        }

        $data = yaml_parse_file($path);
        if (! is_array($data)) {
            throw ConfigException::invalidFormat($path, 'YAML config must parse to an associative array');
        }

        return $data;
    }
}
