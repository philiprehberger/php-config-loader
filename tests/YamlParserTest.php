<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\ConfigLoader;
use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PhilipRehberger\ConfigLoader\Parsers\YamlParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class YamlParserTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/yaml_parser_test_'.uniqid();
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
    public function test_yaml_parser_throws_when_ext_yaml_not_loaded(): void
    {
        if (extension_loaded('yaml')) {
            $this->markTestSkipped('ext-yaml is loaded; cannot test missing extension path.');
        }

        $file = $this->tmpDir.'/config.yaml';
        file_put_contents($file, "key: value\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('ext-yaml PHP extension is required');

        YamlParser::parse($file);
    }

    #[Test]
    public function test_yaml_parser_parses_valid_file_when_ext_loaded(): void
    {
        if (! extension_loaded('yaml')) {
            $this->markTestSkipped('ext-yaml is not loaded; cannot test parsing.');
        }

        $file = $this->tmpDir.'/config.yaml';
        file_put_contents($file, <<<'YAML'
database:
  host: localhost
  port: 3306
  credentials:
    user: admin
    pass: secret
YAML);

        $result = YamlParser::parse($file);

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
    public function test_yaml_parser_loads_yml_extension_via_config_loader(): void
    {
        if (! extension_loaded('yaml')) {
            $this->markTestSkipped('ext-yaml is not loaded; cannot test parsing.');
        }

        $file = $this->tmpDir.'/config.yml';
        file_put_contents($file, "name: MyApp\nversion: 1\n");

        $config = ConfigLoader::load($file);

        $this->assertSame('MyApp', $config->get('name'));
        $this->assertSame(1, $config->get('version'));
    }
}
