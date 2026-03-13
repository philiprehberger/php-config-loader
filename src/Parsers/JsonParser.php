<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Parsers;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;

final class JsonParser
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

        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw ConfigException::invalidFormat($path, 'JSON config must decode to an object/array');
        }

        return $data;
    }
}
