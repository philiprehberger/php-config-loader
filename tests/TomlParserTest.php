<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\ConfigLoader;
use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PhilipRehberger\ConfigLoader\Parsers\TomlParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TomlParserTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/toml_parser_test_'.uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    #[Test]
    public function test_toml_parser_parses_key_value_pairs(): void
    {
        $file = $this->tmpDir.'/config.toml';
        file_put_contents($file, <<<'TOML'
title = "My App"
version = "1.0.0"
TOML);

        $result = TomlParser::parse($file);

        $this->assertSame(['title' => 'My App', 'version' => '1.0.0'], $result);
    }

    #[Test]
    public function test_toml_parser_parses_sections(): void
    {
        $file = $this->tmpDir.'/config.toml';
        file_put_contents($file, <<<'TOML'
[database]
host = "localhost"
port = 3306

[database.credentials]
user = "admin"
pass = "secret"
TOML);

        $result = TomlParser::parse($file);

        $this->assertSame([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'credentials' => [
                    'user' => 'admin',
                    'pass' => 'secret',
                ],
            ],
        ], $result);
    }

    #[Test]
    public function test_toml_parser_parses_data_types(): void
    {
        $file = $this->tmpDir.'/types.toml';
        file_put_contents($file, <<<'TOML'
name = "test"
count = 42
rate = 3.14
enabled = true
disabled = false
negative = -10
TOML);

        $result = TomlParser::parse($file);

        $this->assertSame('test', $result['name']);
        $this->assertSame(42, $result['count']);
        $this->assertSame(3.14, $result['rate']);
        $this->assertTrue($result['enabled']);
        $this->assertFalse($result['disabled']);
        $this->assertSame(-10, $result['negative']);
    }

    #[Test]
    public function test_toml_parser_skips_comments_and_empty_lines(): void
    {
        $file = $this->tmpDir.'/comments.toml';
        file_put_contents($file, <<<'TOML'
# This is a comment
key = "value"

# Another comment
number = 1
TOML);

        $result = TomlParser::parse($file);

        $this->assertSame(['key' => 'value', 'number' => 1], $result);
    }

    #[Test]
    public function test_toml_parser_handles_single_quoted_strings(): void
    {
        $file = $this->tmpDir.'/single.toml';
        file_put_contents($file, "key = 'single quoted'\n");

        $result = TomlParser::parse($file);

        $this->assertSame(['key' => 'single quoted'], $result);
    }

    #[Test]
    public function test_toml_parser_throws_on_invalid_syntax(): void
    {
        $file = $this->tmpDir.'/invalid.toml';
        file_put_contents($file, "not valid toml syntax\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid TOML syntax');

        TomlParser::parse($file);
    }

    #[Test]
    public function test_toml_parser_throws_on_unquoted_string_value(): void
    {
        $file = $this->tmpDir.'/unquoted.toml';
        file_put_contents($file, "key = unquoted\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unsupported TOML value');

        TomlParser::parse($file);
    }

    #[Test]
    public function test_toml_parser_handles_empty_file(): void
    {
        $file = $this->tmpDir.'/empty.toml';
        file_put_contents($file, '');

        $result = TomlParser::parse($file);

        $this->assertSame([], $result);
    }

    #[Test]
    public function test_toml_parser_loads_via_config_loader(): void
    {
        $file = $this->tmpDir.'/app.toml';
        file_put_contents($file, <<<'TOML'
name = "MyApp"
debug = true

[server]
host = "localhost"
port = 8080
TOML);

        $config = ConfigLoader::load($file);

        $this->assertSame('MyApp', $config->get('name'));
        $this->assertTrue($config->get('debug'));
        $this->assertSame('localhost', $config->get('server.host'));
        $this->assertSame(8080, $config->get('server.port'));
    }
}
