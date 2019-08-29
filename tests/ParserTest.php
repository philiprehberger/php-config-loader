<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PhilipRehberger\ConfigLoader\Parsers\JsonParser;
use PhilipRehberger\ConfigLoader\Parsers\PhpParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/parser_test_'.uniqid();
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
    public function test_php_parser_returns_array_from_valid_file(): void
    {
        $file = $this->tmpDir.'/valid.php';
        file_put_contents($file, "<?php\nreturn ['key' => 'value', 'nested' => ['a' => 1]];");

        $result = PhpParser::parse($file);

        $this->assertSame(['key' => 'value', 'nested' => ['a' => 1]], $result);
    }

    #[Test]
    public function test_php_parser_throws_when_file_returns_string(): void
    {
        $file = $this->tmpDir.'/string.php';
        file_put_contents($file, "<?php\nreturn 'not an array';");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP config must return an array');

        PhpParser::parse($file);
    }

    #[Test]
    public function test_php_parser_throws_when_file_returns_null(): void
    {
        $file = $this->tmpDir.'/null.php';
        file_put_contents($file, "<?php\nreturn null;");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP config must return an array');

        PhpParser::parse($file);
    }

    #[Test]
    public function test_php_parser_throws_when_file_returns_integer(): void
    {
        $file = $this->tmpDir.'/integer.php';
        file_put_contents($file, "<?php\nreturn 42;");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP config must return an array');

        PhpParser::parse($file);
    }

    #[Test]
    public function test_php_parser_handles_empty_array(): void
    {
        $file = $this->tmpDir.'/empty.php';
        file_put_contents($file, "<?php\nreturn [];");

        $result = PhpParser::parse($file);

        $this->assertSame([], $result);
    }

    #[Test]
    public function test_json_parser_returns_array_from_valid_file(): void
    {
        $file = $this->tmpDir.'/valid.json';
        file_put_contents($file, json_encode(['host' => 'localhost', 'port' => 3306]));

        $result = JsonParser::parse($file);

        $this->assertSame(['host' => 'localhost', 'port' => 3306], $result);
    }

    #[Test]
    public function test_json_parser_handles_nested_objects(): void
    {
        $file = $this->tmpDir.'/nested.json';
        file_put_contents($file, json_encode(['db' => ['host' => 'localhost', 'options' => ['timeout' => 30]]]));

        $result = JsonParser::parse($file);

        $this->assertSame(['db' => ['host' => 'localhost', 'options' => ['timeout' => 30]]], $result);
    }

    #[Test]
    public function test_json_parser_handles_empty_object(): void
    {
        $file = $this->tmpDir.'/empty.json';
        file_put_contents($file, '{}');

        $result = JsonParser::parse($file);

        $this->assertSame([], $result);
    }

    #[Test]
    public function test_json_parser_throws_on_malformed_json(): void
    {
        $file = $this->tmpDir.'/malformed.json';
        file_put_contents($file, '{"key": "value",}');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('JSON config must decode to an object/array');

        JsonParser::parse($file);
    }

    #[Test]
    public function test_json_parser_throws_on_json_string(): void
    {
        $file = $this->tmpDir.'/string.json';
        file_put_contents($file, '"just a string"');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('JSON config must decode to an object/array');

        JsonParser::parse($file);
    }

    #[Test]
    public function test_json_parser_throws_on_json_number(): void
    {
        $file = $this->tmpDir.'/number.json';
        file_put_contents($file, '42');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('JSON config must decode to an object/array');

        JsonParser::parse($file);
    }

    #[Test]
    public function test_json_parser_throws_on_json_null(): void
    {
        $file = $this->tmpDir.'/null.json';
        file_put_contents($file, 'null');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('JSON config must decode to an object/array');

        JsonParser::parse($file);
    }

    #[Test]
    public function test_json_parser_throws_on_empty_file(): void
    {
        $file = $this->tmpDir.'/empty_content.json';
        file_put_contents($file, '');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('JSON config must decode to an object/array');

        JsonParser::parse($file);
    }
}
