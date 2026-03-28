<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PhilipRehberger\ConfigLoader\Parsers\JsonParser;
use PhilipRehberger\ConfigLoader\Parsers\PhpParser;
use PhilipRehberger\ConfigLoader\Parsers\TomlParser;
use PhilipRehberger\ConfigLoader\Parsers\YamlParser;

final class ConfigLoader
{
    /**
     * Load a single config file and return a Config instance.
     *
     * @throws ConfigException
     */
    public static function load(string $path): Config
    {
        if (! file_exists($path)) {
            throw ConfigException::fileNotFound($path);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $data = match ($extension) {
            'php' => PhpParser::parse($path),
            'json' => JsonParser::parse($path),
            'yaml', 'yml' => YamlParser::parse($path),
            'toml' => TomlParser::parse($path),
            default => throw ConfigException::unsupportedFormat($extension),
        };

        return new Config(EnvResolver::resolve($data));
    }

    /**
     * Load all config files from a directory and return a merged Config.
     * Each file's basename (without extension) becomes a top-level key.
     *
     * @throws ConfigException
     */
    public static function loadDirectory(string $dir): Config
    {
        if (! is_dir($dir)) {
            throw ConfigException::directoryNotFound($dir);
        }

        $merged = [];
        $files = glob($dir.'/*.{php,json,yaml,yml,toml}', GLOB_BRACE);

        if ($files === false) {
            return new Config([]);
        }

        sort($files);

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $config = self::load($file);
            $merged[$key] = $config->all();
        }

        return new Config($merged);
    }
}
