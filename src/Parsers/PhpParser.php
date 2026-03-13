<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Parsers;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;

final class PhpParser
{
    /**
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    public static function parse(string $path): array
    {
        $data = require $path;
        if (! is_array($data)) {
            throw ConfigException::invalidFormat($path, 'PHP config must return an array');
        }

        return $data;
    }
}
